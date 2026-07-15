<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {

    public $archivedSections = [];

    public function mount() {
        $this->loadArchived();
    }

    public function loadArchived() {
        // Ambil section yang diarsip ATAU memiliki project yang diarsip
        $this->archivedSections = Auth::user()->sections()
            ->where(function ($query) {
                $query->whereNotNull('archived_at')
                      ->orWhereHas('projects', function ($q) {
                          $q->whereNotNull('archived_at');
                      });
            })
            ->with('projects') // Load SEMUA project dulu, lalu kita filter di memori
            ->get()
            ->map(function ($section) {
                if ($section->archived_at === null) {
                    // Jika section masih aktif, TAMPILKAN HANYA project yang diarsip
                    $section->setRelation('projects', $section->projects->whereNotNull('archived_at')->values());
                } else {
                    // Jika section sudah diarsip, TAMPILKAN SEMUA project-nya
                    $section->setRelation('projects', $section->projects->values());
                }
                return $section;
            })
            ->filter(function ($section) {
                // Pastikan kita tidak menampilkan section yang kosong
                return $section->projects->count() > 0;
            })
            ->sortByDesc(function ($section) {
                // Urutkan berdasarkan yang paling baru diarsip
                $latestProject = $section->projects->max('archived_at');
                return max($section->archived_at, $latestProject);
            })->values();
    }

    public function restoreSection($sectionId) {
        $section = Auth::user()->sections()->find($sectionId);
        if ($section) {
            $section->update(['archived_at' => null]);
            $section->projects()->whereNotNull('archived_at')->update(['archived_at' => null]);
            $this->loadArchived();
            $this->dispatch('project-updated');
        }
    }

    public function deleteSection($sectionId) {
        $section = Auth::user()->sections()->find($sectionId);
        if ($section) {
            // Jika section-nya diarsip, hapus section (otomatis menghapus projects karena cascade)
            if ($section->archived_at !== null) {
                $section->delete();
            } else {
                // Jika section aktif, hapus HANYA project yang diarsip
                $section->projects()->whereNotNull('archived_at')->delete();
            }
            $this->loadArchived();
            $this->dispatch('project-updated');
        }
    }

    public function restoreProject($projectId) {
        $project = Project::where('user_id', Auth::id())->find($projectId);
        if ($project) {
            $project->update(['archived_at' => null]);
            
            // Jika kita merestore project dari section yang terarsip
            if ($project->section && $project->section->archived_at !== null) {
                $section = $project->section;
                
                // Sebelum membuat section aktif kembali, pastikan project lain yang ada di dalamnya
                // di-archive secara eksplisit agar tidak ikut ter-restore ke dashboard.
                $section->projects()->where('project_id', '!=', $project->project_id)
                                    ->whereNull('archived_at')
                                    ->update(['archived_at' => $section->archived_at]);
                                    
                // Restore section agar project yang dipilih bisa tampil di dashboard
                $section->update(['archived_at' => null]);
            }
            
            $this->loadArchived();
            $this->dispatch('project-updated');
        }
    }

    public function deleteProject($projectId) {
        $project = Project::where('user_id', Auth::id())->find($projectId);
        if ($project) {
            $project->delete();
            $this->loadArchived();
            $this->dispatch('project-updated');
        }
    }
}; ?>

<div class="p-10 max-w-7xl mx-auto">

    <!-- Header -->
    <header class="flex justify-between items-center mb-8">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 text-[18px] text-subtext-100 hover:text-secondary-200 transition-colors">
            <x-icons.chevron rotate="180" size="w-3.5 h-3.5" color="currentColor"/>
            <span class="text-text-70 text-app-subtitle-1 font-semibold">{{ __('Archive') }}</span>
        </a>
    </header>

    @if(count($archivedSections) === 0)
        {{-- Empty State --}}
        <div class="flex flex-col items-center justify-center mt-16">
            <x-empty-archive class="w-64 h-64 mb-6 opacity-80" />
            <h2 class="text-app-heading-1 text-subtext-80 mb-2">{{ __('Your archive is empty') }}</h2>
            <p class="text-app-subfeature text-subtext-70">{{ __('Archived sections will appear here.') }}</p>
        </div>
    @else
        <div class="space-y-12">
            @foreach($archivedSections as $section)
                <div class="relative">

                    {{-- Section Header --}}
                    <div class="relative z-50 flex justify-between items-center border-b border-brand-150 pb-2 mb-6" x-data="{ menuOpen: false }">
                        <div class="min-w-0 flex-1">
                            <h2 class="text-2xl font-merriweather text-text-100 truncate">
                                {{ $section->title }}
                            </h2>
                            <p class="text-[12px] text-subtext-90 mt-0.5">
                                @if($section->archived_at)
                                    {{ __('Archived') }} {{ $section->archived_at->diffForHumans() }} &middot;
                                @else
                                    {{ __('Contains archived projects') }} &middot;
                                @endif
                                {{ $section->projects->count() }} {{ $section->projects->count() === 1 ? __('project') : __('projects') }}
                            </p>
                        </div>

                        <div class="relative shrink-0 ml-4">
                            <button @click="menuOpen = !menuOpen" @click.away="menuOpen = false" class="p-1 hover:bg-brand-150 rounded-md transition-colors cursor-pointer">
                                <svg class="w-6 h-6 text-text-80" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
                            </button>

                            <div x-show="menuOpen" style="display: none;" class="absolute right-0 mt-2 w-48 bg-card-bg border border-card-border rounded-lg shadow-lg z-20 py-1 overflow-hidden">
                                <button
                                    wire:click="restoreSection('{{ $section->section_id }}')"
                                    @click="menuOpen = false"
                                    class="w-full text-left px-4 py-2 text-app-body-medium text-text-80 hover:bg-brand-10 flex items-center gap-3 transition-colors"
                                >
                                    <x-icons.restore class="w-4 h-4 shrink-0 text-text-80" />
                                    {{ __('Restore All') }}
                                </button>
                                <button
                                    @click="$dispatch('open-delete-section-dialog', { id: '{{ $section->section_id }}' }); menuOpen = false"
                                    class="w-full text-left px-4 py-2 text-app-body-medium text-danger-100 hover:bg-danger-100/5 flex items-center gap-3 transition-colors"
                                >
                                    <x-icons.delete class="w-4 h-4 shrink-0 text-danger-100" />
                                    {{ __('Delete Section') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Projects Grid (mirip dashboard) --}}
                    <div class="flex gap-6 overflow-x-auto pb-4 custom-scrollbar"
                         x-data="{ 
                             isDown: false, 
                             isDragging: false,
                             canScroll: false,
                             startX: 0, 
                             scrollLeft: 0,
                             checkScrollable() {
                                 this.canScroll = this.$el.scrollWidth > this.$el.clientWidth;
                                 return this.canScroll;
                             },
                             handleWheel(e) {
                                 if (this.checkScrollable() && e.deltaY !== 0) {
                                     const atLeft = this.$el.scrollLeft <= 0 && e.deltaY < 0;
                                     const atRight = Math.ceil(this.$el.scrollLeft + this.$el.clientWidth) >= this.$el.scrollWidth && e.deltaY > 0;
                                     
                                     if (!atLeft && !atRight) {
                                         e.preventDefault();
                                         this.$el.scrollBy({ left: e.deltaY * 1.5, behavior: 'smooth' });
                                     }
                                 }
                             },
                             startDrag(e) {
                                 if (!this.checkScrollable()) return;
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

                        @forelse($section->projects as $project)
                            <div class="w-44 shrink-0 group" x-data="{ menuOpen: false }">
                                <div class="w-full aspect-[1/1.6] relative mb-3 opacity-75 group-hover:opacity-100 transition-opacity duration-200">
                                    @if($project->cover_image_path)
                                        <div class="absolute inset-y-0 left-0 right-3 w-[calc(100%-12px)] h-full z-20 rounded-l-sm rounded-r-md overflow-hidden shadow-md bg-gradient-to-br from-secondary-50 to-secondary-150 p-[8px] border-r border-black/10 transition-shadow duration-300 group-hover:shadow-xl">
                                            <div class="w-full h-full overflow-hidden rounded-sm bg-brand-100">
                                                <img src="{{ Storage::url($project->cover_image_path) }}" class="w-full h-full object-cover" />
                                            </div>
                                        </div>
                                        <div class="absolute top-2 bottom-2 right-1.5 w-3 bg-gradient-to-r from-[#e9e1da] to-[#d9c5a4] border-y border-r border-[#c6b395] rounded-r-[2px] z-10 shadow-inner"></div>
                                        <div class="absolute inset-y-0 right-0 w-6 bg-[#8a6144] rounded-r-md z-0 shadow-sm border-l border-black/20 transition-shadow duration-300 group-hover:shadow-lg"></div>
                                    @else
                                        <x-default-project class="absolute inset-y-0 left-0 right-3 w-[calc(100%-12px)] h-full text-secondary-100 rounded-l-sm rounded-r-md shadow-md z-20 border-r border-black/10 transition-shadow duration-300 group-hover:shadow-xl" />
                                        <div class="absolute top-2 bottom-2 right-1.5 w-3 bg-gradient-to-r from-[#e9e1da] to-[#d9c5a4] border-y border-r border-[#c6b395] rounded-r-[2px] z-10 shadow-inner"></div>
                                        <div class="absolute inset-y-0 right-0 w-6 bg-[#8a6144] rounded-r-md z-0 shadow-sm border-l border-black/20 transition-shadow duration-300 group-hover:shadow-lg"></div>
                                    @endif

                                    {{-- Restore/Delete overlay --}}
                                    <div class="absolute left-0 right-3 bottom-3 flex flex-col gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-200 px-3 z-30">
                                        <button
                                            wire:click="restoreProject('{{ $project->project_id }}')"
                                            class="w-full flex items-center justify-center gap-1.5 py-1.5 bg-bg-main/85 backdrop-blur-md border border-brand-200/50 rounded-lg text-[12px] text-text-100 font-medium hover:bg-bg-main hover:text-secondary-200 hover:border-secondary-200 transition-all shadow-lg cursor-pointer"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            {{ __('Restore') }}
                                        </button>
                                        <button
                                            @click="$dispatch('open-delete-project-dialog', { id: '{{ $project->project_id }}' })"
                                            class="w-full flex items-center justify-center gap-1.5 py-1.5 bg-bg-main/85 backdrop-blur-md border border-brand-200/50 rounded-lg text-[12px] text-danger-100 font-medium hover:bg-danger-100 hover:text-white hover:border-danger-100 transition-all shadow-lg cursor-pointer"
                                        >
                                            <x-icons.delete class="w-3 h-3" />
                                            {{ __('Delete') }}
                                        </button>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 mb-1">
                                    @if($project->icon_type === 'emoji')
                                        <span class="text-[16px] leading-none shrink-0">{{ $project->icon }}</span>
                                    @elseif($project->icon_type === 'image' && $project->icon)
                                        <img src="{{ Storage::url($project->icon) }}" alt="" class="w-4 h-4 object-cover rounded shrink-0">
                                    @else
                                        <x-icons.sidebar-book class="w-4 h-4 text-secondary-100 shrink-0 group-hover:text-secondary-200 transition-colors" />
                                    @endif
                                    <h3 class="text-app-body-medium text-text-100 truncate group-hover:text-secondary-200 transition-colors">{{ $project->title }}</h3>
                                </div>
                                <p class="text-[11px] font-medium text-subtext-90">{{ $project->created_at->translatedFormat('d F Y') }}</p>
                            </div>
                        @empty
                            <div class="flex items-center justify-center w-full py-8 text-subtext-90 text-app-body-medium italic">
                                {{ __('No projects in this section.') }}
                            </div>
                        @endforelse

                    </div>

                </div>
            @endforeach
        </div>
    @endif
    
    <x-confirm-dialog
        eventName="open-delete-section-dialog"
        title="{{ __('Delete All Projects?') }}"
        description="{{ __('Are you sure you want to permanently delete all archived projects in this section? This action cannot be undone.') }}"
        confirmText="{{ __('Yes, Delete All') }}"
        cancelText="{{ __('Cancel') }}"
        submitAction="deleteSection"
        btnColor="bg-danger-100 hover:bg-danger-100/90 text-white"
    >
        <x-slot:icon>
            <x-icons.delete class="w-15 h-15" />
        </x-slot:icon>
    </x-confirm-dialog>

    <x-confirm-dialog
        eventName="open-delete-project-dialog"
        title="{{ __('Delete Project?') }}"
        description="{{ __('Are you sure you want to permanently delete this project? This action cannot be undone.') }}"
        confirmText="{{ __('Yes, Delete') }}"
        cancelText="{{ __('Cancel') }}"
        submitAction="deleteProject"
        btnColor="bg-danger-100 hover:bg-danger-100/90 text-white"
    >
        <x-slot:icon>
            <x-icons.delete class="w-15 h-15" />
        </x-slot:icon>
    </x-confirm-dialog>

</div>

