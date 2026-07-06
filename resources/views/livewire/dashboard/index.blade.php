<?php

use Livewire\Volt\Component;
use App\Models\Section;

new class extends Component {
    public $sections = [];

    public function mount() {
        $this->loadSections();
    }

    public function loadSections() {
        // Load section beserta project-nya, diurutkan project terbaru di atas
        // Hanya load section yang belum di-archive
        $this->sections = auth()->user()->sections()
            ->whereNull('archived_at')
            ->with(['projects' => function ($query) {
                $query->whereNull('archived_at')->orderBy('created_at', 'desc');
            }])
            ->orderByDesc('created_at')
            ->get();
    }

    public function addSection() {
        $section = auth()->user()->sections()->create([
            'title' => 'Untitled Section'
        ]);

        // Otomatis buat 1 project kosong saat section dibuat
        $section->projects()->create([
            'user_id' => auth()->id(),
            'title' => 'Untitled Project'
        ]);

        $this->loadSections();
        $this->dispatch('project-updated');
    }

    public function renameSection($sectionId, $newTitle) {
        $section = auth()->user()->sections()->find($sectionId);
        if ($section && trim($newTitle) !== '') {
            $section->update(['title' => trim($newTitle)]);
        }
        $this->loadSections();
    }

    public function addProject($sectionId) {
        $section = auth()->user()->sections()->find($sectionId);
        if ($section) {
            $section->projects()->create([
                'user_id' => auth()->id(),
                'title' => 'Untitled Project'
            ]);
            $this->loadSections();
            $this->dispatch('project-updated');
        }
    }

    public function archiveSection($sectionId) {
        $section = auth()->user()->sections()->find($sectionId);
        if ($section) {
            $section->update(['archived_at' => now()]);
            $this->loadSections();
            $this->dispatch('project-updated');
        }
    }
}; ?>

<div class="p-10 max-w-7xl mx-auto" x-data x-init="$nextTick(() => { 
    if (window.location.hash) {
        setTimeout(() => {
            const el = document.getElementById(window.location.hash.substring(1));
            if (el) el.scrollIntoView({behavior: 'smooth'});
        }, 50);
    }
})">
    <x-breadcrumb :items="[
        ['label' => 'Dashboard']
    ]" />

    @php
        $displayName = Auth::user()->profile?->username ?? explode('@', Auth::user()->email)[0];
        $nameLen = strlen($displayName);
        $titleSize = 'text-3xl lg:text-4xl';
        if ($nameLen >= 20) {
            $titleSize = 'text-xl lg:text-2xl';
        } elseif ($nameLen >= 12) {
            $titleSize = 'text-2xl lg:text-3xl';
        }
    @endphp

    <div class="bg-[#F5EFE9] rounded-xl overflow-hidden mb-8 shadow-sm h-[200px] flex justify-between items-end">
        <div class="w-48 md:w-56 lg:w-60 mb-2 shrink-0 pointer-events-none">
            <x-left-dashboard class="w-full h-auto block" />
        </div>
        <div class="flex-1 self-center px-4 md:px-8 z-10 min-w-0 flex flex-col items-center"
             x-data="{
                 overflows: false,
                 distance: 0,
                 duration: 10,
                 checkOverflow() {
                     // Check after fonts render
                     setTimeout(() => {
                         const container = this.$refs.container.clientWidth;
                         const text = this.$refs.text.scrollWidth;
                         if (text > container) {
                             this.overflows = true;
                             this.distance = (text + 48) - container + 24; // 48 for px-6, 24 for buffer
                             this.duration = Math.max(5, this.distance / 15); // slower speed
                         } else {
                             this.overflows = false;
                         }
                     }, 150);
                 }
             }"
             x-init="
                 const resizeObserver = new ResizeObserver(() => {
                     // Add a tiny delay to allow transitions (like sidebar toggle) to settle
                     setTimeout(() => checkOverflow(), 50);
                 });
                 resizeObserver.observe($refs.container);
             "
        >
            <style>
                .welcome-marquee {
                    animation: welcome-slide var(--duration) linear infinite;
                }
                @keyframes welcome-slide {
                    0% { transform: translateX(0); }
                    85%, 100% { transform: translateX(var(--distance)); }
                }
                .mask-image-fade {
                    -webkit-mask-image: linear-gradient(to right, transparent, black 24px, black calc(100% - 24px), transparent);
                    mask-image: linear-gradient(to right, transparent, black 24px, black calc(100% - 24px), transparent);
                }
            </style>
            
            <div x-ref="container" class="w-full overflow-hidden flex" :class="overflows ? 'justify-start mask-image-fade' : 'justify-center'">
                <div x-ref="text" 
                     class="whitespace-nowrap"
                     :class="overflows ? 'welcome-marquee text-left px-6' : 'text-center'"
                     :style="overflows ? `--distance: -${distance}px; --duration: ${duration}s;` : ''"
                >
                    <h1 class="{{ $titleSize }} font-merriweather text-text-100 mb-2">
                        Welcome <span class="font-bold">{{ $displayName }}!</span>
                    </h1>
                </div>
            </div>
            
            <p class="text-app-body-large text-text-80 truncate w-full text-center">Are u ready to spin the <span class="italic">yarn</span>?</p>
        </div>
        <div class="w-48 md:w-56 lg:w-60 shrink-0 pointer-events-none flex justify-end">
            <x-right-dashboard class="w-full h-auto block" />
        </div>
    </div>

    @if(count($sections) === 0)
        <button wire:click="addSection" class="w-full py-3 mb-16 border border-brand-200 rounded-lg text-subtext-80 hover:bg-[#F5EFE9] transition-colors flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add New Section
        </button>

        <div class="flex flex-col items-center justify-center mt-12">
            <x-empty-dashboard class="w-64 h-64 mb-6 opacity-80" />
            <h2 class="text-app-heading-1 text-subtext-80 mb-2">Looks like it's a bit quiet in here...</h2>
            <p class="text-app-body-large text-subtext-70">Ready to create a new one?</p>
        </div>
    @else
        <div class="space-y-12 mb-8">
            @foreach($sections as $section)
                <div id="section-{{ $section->section_id }}" x-data="{ editing: false, newName: '{{ $section->title }}', menuOpen: false }" class="relative scroll-mt-8">

                    <div class="flex justify-between items-center border-b border-brand-150 pb-2 mb-6">
                        <div class="w-full">
                            <h2
                                x-show="!editing"
                                @dblclick="editing = true; setTimeout(() => $refs.nameInput.focus(), 50)"
                                class="text-2xl font-merriweather text-text-100 cursor-pointer select-none hover:text-secondary-200 transition-colors truncate"
                                title="{{ $section->title }}"
                            >
                                {{ \Illuminate\Support\Str::limit($section->title, 30) }}
                            </h2>

                            <div x-show="editing" class="flex items-center gap-3 w-1/2">
                                <input
                                    x-model="newName"
                                    x-ref="nameInput"
                                    maxlength="50"
                                    @keydown.enter="$wire.renameSection('{{ $section->section_id }}', newName); editing = false"
                                    @keydown.escape="editing = false; newName = '{{ $section->title }}'"
                                    @click.away="$wire.renameSection('{{ $section->section_id }}', newName); editing = false"
                                    class="text-2xl font-merriweather text-text-100 bg-transparent border-b border-secondary-200 outline-none w-full focus:ring-0 px-0 py-0"
                                >
                                <span class="text-[12px] text-text-80 font-medium whitespace-nowrap shrink-0" x-text="newName.length + '/50'"></span>
                            </div>
                        </div>

                        <div class="relative shrink-0">
                            <button @click="menuOpen = !menuOpen" @click.away="menuOpen = false" class="p-1 hover:bg-brand-150 rounded-md transition-colors">
                                <svg class="w-6 h-6 text-text-80" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
                            </button>

                            <div x-show="menuOpen" style="display: none;" class="absolute right-0 mt-2 w-48 bg-white border border-brand-150 rounded-lg shadow-lg z-50 py-1 overflow-hidden">
                                <button wire:click="addProject('{{ $section->section_id }}')" @click="menuOpen = false" class="w-full text-left px-4 py-2 text-app-body-medium text-text-80 hover:bg-brand-10 flex items-center gap-3 transition-colors">
                                    <x-icons.add class="w-4 h-4 shrink-0 text-text-80" /> Add Project
                                </button>
                                <button @click="editing = true; menuOpen = false; setTimeout(() => $refs.nameInput.focus(), 50)" class="w-full text-left px-4 py-2 text-app-body-medium text-text-80 hover:bg-brand-10 flex items-center gap-3 transition-colors">
                                    <x-icons.rename class="w-4 h-4 shrink-0 text-text-80" /> Rename
                                </button>
                                <button @click="$dispatch('open-archive-section-dialog', { id: '{{ $section->section_id }}' }); menuOpen = false" class="w-full text-left px-4 py-2 text-app-body-medium text-text-80 hover:bg-brand-10 flex items-center gap-3 transition-colors">
                                    <x-icons.archive class="w-4 h-4 shrink-0 text-text-80" /> Archive
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-6 overflow-x-auto pb-4 custom-scrollbar"
                         x-data="{ 
                             isDown: false, 
                             isDragging: false,
                             canScroll: false,
                             startX: 0, 
                             scrollLeft: 0,
                             checkScrollable() {
                                 this.canScroll = this.$el.scrollWidth > this.$el.clientWidth;
                             },
                             handleWheel(e) {
                                 if (this.canScroll && e.deltaY !== 0) {
                                     const atLeft = this.$el.scrollLeft <= 0 && e.deltaY < 0;
                                     const atRight = Math.ceil(this.$el.scrollLeft + this.$el.clientWidth) >= this.$el.scrollWidth && e.deltaY > 0;
                                     
                                     if (!atLeft && !atRight) {
                                         e.preventDefault();
                                         this.$el.scrollBy({ left: e.deltaY * 1.5, behavior: 'smooth' });
                                     }
                                 }
                             },
                             startDrag(e) {
                                 this.checkScrollable();
                                 if (!this.canScroll) return;
                                 this.isDown = true;
                                 this.isDragging = false;
                                 this.startX = e.pageX - this.$el.offsetLeft;
                                 this.scrollLeft = this.$el.scrollLeft;
                             },
                             stopDrag() {
                                 this.isDown = false;
                                 setTimeout(() => this.isDragging = false, 200);
                             },
                             doDrag(e) {
                                 if (!this.isDown) return;
                                 const x = e.pageX - this.$el.offsetLeft;
                                 const walk = (x - this.startX) * 1.5;
                                 if (Math.abs(walk) > 5) {
                                     this.isDragging = true;
                                     e.preventDefault();
                                 }
                                 this.$el.scrollLeft = this.scrollLeft - walk;
                             }
                         }"
                         :class="{ 
                             'cursor-grab active:cursor-grabbing': canScroll, 
                             '[&_a]:pointer-events-none [&_button]:pointer-events-none': isDragging 
                         }"
                         x-init="checkScrollable()"
                         @mouseenter="checkScrollable()"
                         @wheel="handleWheel($event)"
                         @mousedown="startDrag($event)"
                         @mouseleave="stopDrag()"
                         @mouseup="stopDrag()"
                         @mousemove="doDrag($event)"
                         @click.capture="if(isDragging) { $event.preventDefault(); $event.stopPropagation(); }"
                    >

                        @foreach($section->projects as $project)
                            <a href="{{ route('projects.show', $project->project_id) }}" draggable="false" @dragstart.prevent wire:navigate class="w-44 shrink-0 group cursor-pointer block select-none">
                                <div class="w-full aspect-[1/1.6] relative mb-3">
                                    @if($project->cover_image_path)
                                        <img src="{{ Storage::url($project->cover_image_path) }}" class="absolute inset-y-0 left-0 right-3 w-[calc(100%-12px)] h-full object-cover rounded-l-sm rounded-r-md shadow-md z-20 border-r border-black/10 transition-shadow duration-300 group-hover:shadow-xl" />
                                        <div class="absolute top-2 bottom-2 right-1.5 w-3 bg-gradient-to-r from-[#E8E3D9] to-[#D5C6A9] border-y border-r border-[#C4B7A3] rounded-r-[2px] z-10 shadow-inner"></div>
                                        <div class="absolute inset-y-0 right-0 w-6 bg-[#8C7558] rounded-r-md z-0 shadow-sm border-l border-black/20 transition-shadow duration-300 group-hover:shadow-lg"></div>
                                    @else
                                        <x-default-project class="absolute inset-y-0 left-0 right-3 w-[calc(100%-12px)] h-full text-[#B69F78] rounded-l-sm rounded-r-md shadow-md z-20 border-r border-black/10 transition-shadow duration-300 group-hover:shadow-xl" />
                                        <div class="absolute top-2 bottom-2 right-1.5 w-3 bg-gradient-to-r from-[#E8E3D9] to-[#D5C6A9] border-y border-r border-[#C4B7A3] rounded-r-[2px] z-10 shadow-inner"></div>
                                        <div class="absolute inset-y-0 right-0 w-6 bg-[#8C7558] rounded-r-md z-0 shadow-sm border-l border-black/20 transition-shadow duration-300 group-hover:shadow-lg"></div>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2 mb-1">
                                    @if($project->icon_type === 'emoji')
                                        <span class="text-[16px] leading-none shrink-0">{{ $project->icon }}</span>
                                    @elseif($project->icon_type === 'image' && $project->icon)
                                        <img src="{{ asset('storage/' . $project->icon) }}" alt="" class="w-4 h-4 object-cover rounded shrink-0">
                                    @else
                                        <x-icons.sidebar-book class="w-4 h-4 text-text-80 shrink-0 group-hover:text-secondary-200 transition-colors" />
                                    @endif
                                    <h3 class="text-app-body-medium text-text-100 truncate group-hover:text-secondary-200 transition-colors">{{ $project->title }}</h3>
                                </div>
                                <p class="text-[11px] font-medium text-subtext-90">{{ $project->created_at->format('d F Y') }}</p>
                            </a>
                        @endforeach

                        <button wire:click="addProject('{{ $section->section_id }}')" class="w-44 shrink-0 aspect-[2/3] border-2 border-dashed border-brand-200 rounded-xl flex flex-col items-center justify-center text-subtext-80 hover:border-secondary-200 hover:text-secondary-200 hover:bg-[#F5EFE9] transition-all group mb-3 select-none">
                            <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span class="text-sm font-medium">New Project</span>
                        </button>

                    </div>

                </div>
            @endforeach
        </div>

    @endif

    <!-- Floating Action Button for Add Section -->
    <button wire:click="addSection" class="group fixed bottom-10 right-10 flex flex-row-reverse items-center bg-secondary-200 text-brand-10 rounded-full h-14 w-14 hover:w-44 transition-all duration-300 ease-out shadow-xl hover:bg-secondary-250 overflow-hidden z-50 focus:outline-none">
        <div class="flex items-center justify-center shrink-0 w-14 h-14">
            <svg class="w-6 h-6 transition-transform duration-300 group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
        </div>
        <span class="whitespace-nowrap font-medium text-[15px] opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex-1 text-right pr-2">
            Add Section
        </span>
    </button>

    <x-confirm-dialog
        eventName="open-archive-section-dialog"
        title="Archive this Section?"
        description="Are you sure you want to archive this section? You can restore it from the Archive page."
        confirmText="Yes, Archive"
        cancelText="Cancel"
        submitAction="archiveSection"
        iconColor="text-warning-100"
        iconBg="bg-warning-100/10"
        btnColor="bg-warning-100 hover:bg-warning-100/90 text-white"
    >
        <x-slot:icon>
            <x-icons.archive class="w-12 h-12" />
        </x-slot:icon>
    </x-confirm-dialog>
</div>
