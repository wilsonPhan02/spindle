<?php

use Livewire\Volt\Component;
use App\Models\Project;
use App\Models\Note;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public Project $project;

    // Variabel terpisah untuk menjamin data tersimpan aman
    public string $title = '';
    public string $synopsis = '';

    public $newCategoryName = '';
    public $cover_image;

    // Daftar 10 notes yang terakhir diedit, untuk ditampilkan di card Notes
    public $recentNotes = [];

    // Daftar karakter untuk ditampilkan di card Character
    public $recentCharacters = [];

    public function mount(Project $project) {
        $this->project = $project;
        $this->title = $project->title;
        $this->synopsis = $project->synopsis ?? '';

        $this->recentNotes = Note::where('project_id', $project->project_id)
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        $this->recentCharacters = $project->characters()
            ->with('hashtags')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();
    }

    public function saveTitle() {
        $this->title = trim($this->title) ?: 'Untitled Project';
        $this->project->update(['title' => $this->title]);
    }

    public function saveSynopsis() {
        $this->project->update(['synopsis' => trim($this->synopsis)]);
    }

    public function addCategory() {
        if (trim($this->newCategoryName) !== '') {
            $this->project->categories()->create([
                'name' => strtolower(substr(trim($this->newCategoryName), 0, 20))
            ]);
            $this->newCategoryName = '';
            $this->project->touch();
            $this->project->load('categories');
        }
    }

    public function renameCategory($id, $newName) {
        $category = $this->project->categories()->find($id);
        if ($category && trim($newName) !== '') {
            $category->update(['name' => strtolower(substr(trim($newName), 0, 20))]);
            $this->project->touch();
        }
        $this->project->load('categories');
    }

    public function deleteCategory($id) {
        $this->project->categories()->find($id)?->delete();
        $this->project->touch();
        $this->project->load('categories');
    }

    public function updatedCoverImage() {
        $this->validate(['cover_image' => 'image|max:5048']);
        if ($this->project->cover_image_path && Storage::disk('public')->exists($this->project->cover_image_path)) {
            Storage::disk('public')->delete($this->project->cover_image_path);
        }
        $path = $this->cover_image->store('covers', 'public');
        $this->project->update(['cover_image_path' => $path]);
        $this->cover_image = null;
        $this->project->refresh();
    }

    public function deleteCover() {
        if ($this->project->cover_image_path && Storage::disk('public')->exists($this->project->cover_image_path)) {
            Storage::disk('public')->delete($this->project->cover_image_path);
        }
        $this->project->update(['cover_image_path' => null]);
        $this->project->refresh();
    }

    public function archiveProject() {
        $this->project->update(['archived_at' => now()]);
        $this->redirect(route('dashboard'), navigate: true);
    }

    public function togglePin() {
        if (!$this->project->is_pinned) {
            $pinnedCount = auth()->user()->projects()->where('is_pinned', true)->count();
            if ($pinnedCount >= 10) {
                $this->dispatch('limit-reached'); // We'll listen to this via Alpine
                return;
            }
        }
        $this->project->is_pinned = !$this->project->is_pinned;
        $this->project->save();
        $this->dispatch('project-pinned-updated');
    }
}; ?>

<div>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #D5C6A9; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #B69F78; }
    </style>

    <div class="p-6 lg:p-10 max-w-7xl mx-auto">
        <header class="flex justify-between items-center mb-10">
            <div class="flex items-center gap-3 text-[18px] text-[#7A7A7A]">
                <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-[#8C7558] transition-colors">Dashboard</a>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-[#2C2C2C] font-semibold truncate">{{ $title }}</span>
            </div>
            <x-logo class="h-8 w-auto text-text-100" />
        </header>

        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6 mb-16 items-stretch">

            <div
                x-data="{ hoverCover: false }"
                @mouseover="hoverCover = true"
                @mouseleave="hoverCover = false"
                class="relative w-full lg:w-[320px] xl:w-[360px] shrink-0 aspect-[1/1.6] z-10"
            >
            {{-- last benerin --}}
                @if($project->cover_image_path)
                    <!-- Image / Front Cover -->
                    <img src="{{ Storage::url($project->cover_image_path) }}" 
                        class="absolute top-0 left-0 w-[calc(100%-16px)] h-full object-cover rounded-l-md rounded-r-xl shadow-md z-20 border-r border-black/10" />
                    <!-- Book pages on the right -->
                    <div class="absolute top-3.5 bottom-3.5 right-2 w-4 bg-gradient-to-r from-[#E8E3D9] to-[#D5C6A9] border-y border-r border-[#C4B7A3] rounded-r-sm z-10 shadow-inner"></div>
                    <!-- Back cover sticking out -->
                    <div class="absolute inset-y-0 right-0 w-8 bg-[#8C7558] rounded-r-xl z-0 shadow-xl border-l border-black/20"></div>
                @else
                    <x-default-project class="absolute inset-y-0 left-0 right-4 w-[calc(100%-16px)] h-full text-[#B69F78] rounded-l-md rounded-r-xl shadow-md z-20 border-r border-black/10" />
                    <div class="absolute top-3.5 bottom-3.5 right-2 w-4 bg-gradient-to-r from-[#E8E3D9] to-[#D5C6A9] border-y border-r border-[#C4B7A3] rounded-r-sm z-10 shadow-inner"></div>
                    <div class="absolute inset-y-0 right-0 w-8 bg-[#8C7558] rounded-r-xl z-0 shadow-xl border-l border-black/20"></div>
                @endif

                <div x-show="hoverCover" x-transition class="absolute bottom-5 left-5 z-30 flex gap-2">
                    <label class="flex items-center gap-2 px-3 py-1.5 bg-[#1F2328]/95 border border-white/10 rounded-md cursor-pointer hover:bg-[#2A2F36] transition-colors shadow-lg">
                        <x-icons.upload class="w-4 h-4 text-white" />
                        <span class="text-[13px] text-white font-medium tracking-wide">Upload Cover</span>
                        <input type="file" wire:model.live="cover_image" class="hidden" accept="image/*">
                    </label>

                    @if($project->cover_image_path)
                        <button wire:click="deleteCover" class="flex items-center gap-2 px-3 py-1.5 bg-[#1F2328]/95 border border-white/10 rounded-md cursor-pointer hover:bg-[#2A2F36] transition-colors shadow-lg">
                            <x-icons.delete class="w-4 h-4 text-[#E64C4C]" />
                            <span class="text-[13px] text-[#E64C4C] font-medium tracking-wide">Remove</span>
                        </button>
                    @endif
                </div>

                <div wire:loading.flex wire:target="cover_image" class="absolute inset-y-0 left-0 right-4 w-[calc(100%-16px)] bg-[#F5EFE9]/70 backdrop-blur-md z-40 items-center justify-center rounded-l-md rounded-r-lg transition-all">
                    <svg class="animate-spin h-8 w-8 text-secondary-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </div>
            </div>

            <div class="flex-1 min-w-0 relative">
                <div class="static lg:absolute inset-0 bg-brand-50 border border-brand-150 p-8 pb-16 rounded-lg flex flex-col">

                <div class="flex justify-between items-start mb-4">
                    <div class="flex-1 min-w-0 mr-4">
                        <x-icons.sidebar-book class="w-8 h-8 text-secondary-100 mb-3" />

                        @php
                            $titleLen = strlen($title);
                            $dtlTitleSize = 'text-[32px]';
                            if ($titleLen >= 25) { $dtlTitleSize = 'text-[22px]'; } elseif ($titleLen >= 15) { $dtlTitleSize = 'text-[26px]'; }
                        @endphp

                        <div x-data="{ editingTitle: false, hoverTitle: false, localTitle: @entangle('title') }" @mouseover="hoverTitle = true" @mouseleave="hoverTitle = false" x-on:livewire:navigated.window="localTitle = $wire.title" class="flex items-center gap-3 group">
                            <h1
                                x-show="!editingTitle"
                                @dblclick="editingTitle = true; setTimeout(() => $refs.titleInput.focus(), 50)"
                                class="{{ $dtlTitleSize }} text-app-title-1 text-text-80 transition-colors leading-tight cursor-pointer select-none group-hover:text-secondary-200 truncate"
                            >
                                <span x-text="localTitle || 'Untitled Project'"></span>
                            </h1>
                            <button x-show="hoverTitle && !editingTitle" @click="editingTitle = true; setTimeout(() => $refs.titleInput.focus(), 50)" class="stroke-2 text-secondary-200 transition-colors shrink-0">
                                <x-icons.rename class="w-5 h-5" />
                            </button>

                            <input
                                x-show="editingTitle"
                                x-model="localTitle"
                                x-ref="titleInput"
                                @click.outside="if(editingTitle) { $wire.saveTitle(); editingTitle = false; }"
                                @keydown.enter="$wire.saveTitle(); editingTitle = false"
                                @keydown.escape="editingTitle = false; localTitle = '{{ addslashes($project->title) }}'"
                                class="{{ $dtlTitleSize }} text-app-title-1 text-text-80 bg-transparent border-b-2 border-secondary-100 outline-none w-full focus:border-secondary-200 focus:ring-0 px-0 py-1"
                            />
                        </div>
                        <p class="text-app-body-large text-subtext-100 mt-2 truncate">from <span class="text-text-80" title="{{ $project->section->title ?? 'Uncategorized' }}">{{ \Illuminate\Support\Str::limit($project->section->title ?? 'Uncategorized', 30) }}</span></p>
                    </div>

                    <div class="flex items-center gap-3 shrink-0"
                        @limit-reached.window="alert('Pinned projects have reached the maximum limit (10). You cannot pin more projects.')">

                        <button wire:click="togglePin"
                            title="{{ $project->is_pinned ? 'Unpin Project' : 'Pin Project' }}"
                            class="text-secondary-100 hover:text-secondary-200 transition-colors focus:outline-none">
                            @if($project->is_pinned)
                                <x-icons.bookmark-solid class="w-5 h-5" />
                            @else
                                <x-icons.bookmark class="w-5 h-5" />
                            @endif
                        </button>
                    </div>
                </div>

                <div x-data="{
                    addingCat: false,
                    addCount: 0,
                    isDown: false,
                    startX: 0,
                    scrollLeft: 0,
                    startDrag(e) {
                        this.isDown = true;
                        this.startX = e.pageX - this.$refs.scrollContainer.offsetLeft;
                        this.scrollLeft = this.$refs.scrollContainer.scrollLeft;
                    },
                    endDrag() {
                        this.isDown = false;
                    },
                    doDrag(e) {
                        if (!this.isDown) return;
                        e.preventDefault();
                        const x = e.pageX - this.$refs.scrollContainer.offsetLeft;
                        const walk = (x - this.startX) * 1.5;
                        this.$refs.scrollContainer.scrollLeft = this.scrollLeft - walk;
                    }
                }" class="mb-0 w-full max-w-full min-w-0">
                    <div class="flex items-center gap-3 mb-2">
                        <x-icons.category class="w-4 h-4 text-text-80" />
                        <span class="text-app-feature text-text-80">Categories</span>
                    </div>

                    <div
                        x-ref="scrollContainer"
                        @mousedown="startDrag"
                        @mouseleave="endDrag"
                        @mouseup="endDrag"
                        @mousemove="doDrag"
                        @wheel="$event.preventDefault(); $el.scrollLeft += ($event.deltaX !== 0 ? $event.deltaX : $event.deltaY)"
                        class="flex gap-2 items-center overflow-x-auto pb-5 cursor-grab active:cursor-grabbing scroll-smooth [&::-webkit-scrollbar]:h-1.5 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-secondary-100 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-button]:hidden [scrollbar-width:thin] [scrollbar-color:#A08866_transparent]"
                    >
                        <button x-show="!addingCat" @click="addingCat = true; addCount = 0; setTimeout(() => $refs.catInput.focus(), 50)" class="shrink-0 flex items-center gap-1 rounded-full bg-brand-100 hover:bg-brand-200 text-secondary-100 hover:text-secondary-150 transition-colors p-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            @if($project->categories->count() == 0)
                                <span class="text-app-desc-feature">Add category</span>
                            @endif
                        </button>

                        <div x-show="addingCat" class="flex items-center gap-1 bg-brand-100 pl-3 pr-1.5 py-2 rounded-full border border-brand-150 relative shrink-0">
                                <input type="text" maxlength="20" 
                                        @input="addCount = $event.target.value.length" x-model="$wire.newCategoryName" x-ref="catInput" 
                                        @keyup.enter="addingCat = false; $wire.addCategory()" 
                                        @blur="if(addingCat) { addingCat = false; $wire.set('newCategoryName', ''); }"
                                        @keydown.escape="addingCat = false; $wire.set('newCategoryName', '');" 
                                        placeholder="Category..." 
                                        class="w-28 text-app-body-small bg-transparent border-b border-secondary-100 outline-none px-1 py-0 text-text-70 focus:border-secondary-200"/>
                                
                                <button @mousedown.prevent="addingCat = false; $wire.addCategory()" 
                                        class="text-secondary-150 hover:text-secondary-200 transition-colors shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>

                            <span class="absolute -bottom-3.5 right-1 text-[9px] text-subtext-90 font-medium" x-text="addCount + '/20'"></span>
                        </div>

                        @foreach($project->categories as $category)
                            <div x-data="{ editingCat: false, hoverCat: false, count: {{ strlen($category->name) }} }" 
                                 @mouseenter="hoverCat = true" 
                                 @mouseleave="hoverCat = false"
                                 class="relative group shrink-0">
                                
                                <div x-show="!editingCat" @dblclick="editingCat = true; $nextTick(() => $refs.editCatInput.focus())" title="{{ $category->name }}" class="cursor-pointer px-3 py-2 rounded-full bg-brand-100 text-app-body-small text-text-80 flex gap-1.5 items-center border border-transparent group-hover:border-brand-150 transition-colors">
                                    <span class="select-none truncate max-w-[130px] block">
                                        {{ $category->name }}
                                    </span>
                                    
                                    <button wire:click.stop="deleteCategory('{{ $category->category_id }}')" x-show="hoverCat" class="text-secondary-100 hover:text-secondary-200 transition-colors shrink-0 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>

                                <div x-show="editingCat" class="flex items-center gap-1 bg-brand-100 pl-3 pr-1.5 py-2 rounded-full border border-secondary-150 relative">
                                        <input x-ref="editCatInput" 
                                            value="{{ $category->name }}" 
                                            maxlength="20" 
                                            @input="count = $event.target.value.length" 
                                            @keyup.enter="$el.blur()" 
                                            @blur="editingCat = false; $wire.renameCategory('{{ $category->category_id }}', $el.value)" 
                                            @keydown.escape="editingCat = false; $refs.editCatInput.value = '{{ addslashes($category->name) }}'; 
                                            count = {{ strlen($category->name) }}" 
                                            class="w-24 text-app-body-small bg-transparent border-b border-secondary-100 outline-none px-1 py-0 text-text-70 focus:border-secondary-200" />
                                        
                                        <button @mousedown.prevent="$refs.editCatInput.blur()" class="text-secondary-100 hover:text-secondary-200 transition-colors shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </button>

                                    <span class="absolute -bottom-3.5 right-1 text-[9px] text-subtext-90 font-medium" x-text="count + '/20'"></span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="h-px bg-brand-100 w-full mb-4"></div>

                <div x-data="{
                    editingSyn: false,
                    hoverSyn: false,
                    showMore: false,
                    isOverflowing: false,
                    localSyn: @entangle('synopsis'),
                    checkOverflow() {
                        if(this.$refs.synText) {
                            this.isOverflowing = this.$refs.synText.scrollHeight > 140 && this.localSyn.trim() !== '';
                        }
                    },
                    init() {
                        setInterval(() => this.checkOverflow(), 1000);
                        this.$watch('localSyn', () => this.$nextTick(() => this.checkOverflow()));
                        setTimeout(() => this.checkOverflow(), 200);
                    }
                }"
                @resize.window="checkOverflow()"
                class="w-full lg:flex-1 lg:min-h-0 flex flex-col">
                    <div @mouseover="hoverSyn = true" @mouseleave="hoverSyn = false" class="flex items-center gap-3 mb-2 shrink-0">
                        <span class="text-app-heading-2 text-text-80">Synopsis</span>
                        <button x-show="hoverSyn && !editingSyn" 
                                @click="editingSyn = true; setTimeout(() => $refs.synInput.focus(), 50)" 
                                class="text-[#A08866] hover:text-secondary-200 transition-colors">
                            <x-icons.rename class="w-4 h-4" />
                        </button>
                    </div>

                    <div x-show="!editingSyn" class="lg:flex-1 lg:min-h-0 flex flex-col relative pb-16">
                        <div class="relative group w-full lg:flex-1 lg:min-h-0 shrink flex flex-col">
                            <div
                                x-ref="synText"
                                @dblclick="editingSyn = true; setTimeout(() => $refs.synInput.focus(), 50)"
                                class="text-app-body-medium text-text-80 leading-[1.6] select-none cursor-pointer w-full shrink min-h-0"
                                :class="showMore ? 'lg:overflow-y-auto pr-3 custom-scrollbar lg:flex-1' : 'max-h-[140px] overflow-hidden'"
                            >
                                <div x-show="localSyn.trim() !== ''" class="whitespace-pre-wrap" x-text="localSyn.trim()"></div>
                                <div x-show="localSyn.trim() === ''" class="text-app-desc-feature text-text-60">Write your synopsis here!</div>
                            </div>
                            <div x-show="isOverflowing && !showMore" class="absolute bottom-0 left-0 w-full h-16 bg-gradient-to-t from-[#F5EFE9] via-[#F5EFE9]/90 to-transparent pointer-events-none"></div>
                        </div>

                        <div x-show="isOverflowing && localSyn.trim() !== '' && !editingSyn" class="absolute bottom-6 left-0 w-full flex justify-center">
                            <button
                                @click="showMore = !showMore; if(!showMore) { $nextTick(() => checkOverflow()); $refs.synText.scrollTop = 0; }"
                                class="text-app-body-medium font-bold text-[#2C2C2C] hover:text-secondary-200 flex items-center gap-1 z-10 px-4 py-1 rounded-full bg-[#EAE1D5]/50 hover:bg-[#EAE1D5] transition-colors"
                            >
                                <svg class="w-4 h-4 transition-transform" :class="showMore ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                <span x-text="showMore ? 'Show Less' : 'Show More'"></span>
                            </button>
                        </div>
                    </div>

                    <textarea
                        x-show="editingSyn"
                        x-model="localSyn"
                        x-ref="synInput"
                        @click.outside="if(editingSyn) { $wire.saveSynopsis(); editingSyn = false; }"
                        @keydown.ctrl.enter="$wire.saveSynopsis(); editingSyn = false"
                        @keydown.escape="editingSyn = false; localSyn = `{{ addslashes($project->synopsis ?? '') }}`"
                        class="w-full mt-2 lg:flex-1 lg:min-h-0 min-h-[150px] text-app-body-medium text-text-60 bg-transparent border-2 border-[#D5C6A9] rounded-md outline-none resize-none p-4 focus:border-[#A08866] transition-colors custom-scrollbar"
                    ></textarea>
                </div>

                <div class="absolute bottom-4 text-app-desc-feature text-[11px] left-8 right-8 flex justify-between items-center">
                    <button
                        wire:click="archiveProject"
                        wire:confirm="Are you sure you want to archive this project? You can restore it from the Archive page."
                        class="flex items-center gap-1.5 py-2 px-4 rounded-md border border-text-70 hover:border-danger-100/80 text-text-70 hover:text-danger-100 hover:bg-danger-100/10 transition-colors opacity-70 hover:opacity-100">
                        <x-icons.archive class="w-3.5 h-3.5" /> Move To Archive
                    </button>
                    <div
                        x-data="{
                            diffSeconds: 0,
                            clientStartTime: 0,
                            diffText: '{{ $project->updated_at->diffForHumans() }}',
                            lastRenderTime: null,
                            init() {
                                this.updateTime();
                                setInterval(() => this.updateTime(), 1000);
                            },
                            updateTime() {
                                if (this.$refs.renderTime) {
                                    const newRenderTime = parseFloat(this.$refs.renderTime.innerText);
                                    if (this.lastRenderTime !== newRenderTime) {
                                        this.diffSeconds = Math.abs(parseInt(this.$refs.serverDiff.innerText)) || 0;
                                        this.lastRenderTime = newRenderTime;
                                        this.clientStartTime = Math.floor(Date.now() / 1000);
                                    }
                                }
                                
                                const now = Math.floor(Date.now() / 1000);
                                const elapsedSinceRender = now - this.clientStartTime;
                                const totalDiff = this.diffSeconds + Math.max(0, elapsedSinceRender);
                                
                                if (totalDiff < 60) {
                                    this.diffText = 'just now';
                                } else if (totalDiff < 120) {
                                    this.diffText = '1 minute ago';
                                } else if (totalDiff < 3600) {
                                    this.diffText = Math.floor(totalDiff / 60) + ' minutes ago';
                                } else if (totalDiff < 7200) {
                                    this.diffText = '1 hour ago';
                                } else if (totalDiff < 86400) {
                                    this.diffText = Math.floor(totalDiff / 3600) + ' hours ago';
                                } else {
                                    this.diffText = '{{ $project->updated_at->diffForHumans() }}';
                                }
                            }
                        }"
                        class="text-subtext-100"
                    >
                        <span x-ref="serverDiff" class="hidden">{{ abs(now()->timestamp - $project->updated_at->timestamp) }}</span>
                        <span x-ref="renderTime" class="hidden">{{ microtime(true) }}</span>
                        Last Edited <span x-text="diffText"></span>
                    </div>
                </div>
            </div>
            </div>
        </div>

        <div>
            <div class="flex items-center gap-6 mb-8">
                <h2 class="text-[28px] text-web-heading-2">Workspace</h2>
                <div class="flex-1 h-px bg-brand-150"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach([
                    ['title' => 'Structure', 'icon' => 'no-structure', 'desc' => 'You Didn\'t Have Any Chapters!', 'btn' => 'View Structure', 'route' => null],
                    ['title' => 'Character', 'icon' => 'no-character', 'desc' => 'You Didn\'t Have Any Characters!', 'btn' => 'View Character', 'route' => 'projects.characters'],
                    ['title' => 'Notes', 'icon' => 'no-notes', 'desc' => 'You Didn\'t Have Any Notes!', 'btn' => 'View Notes', 'route' => 'projects.notes']
                ] as $workspace)

                <div class="bg-brand-100 rounded-md p-4 flex flex-col justify-between h-[396px] border border-brand-150">
                    <h3 class="text-app-heading-2 text-text-100 mb-4 px-1">{{ $workspace['title'] }}</h3>

                    @if($workspace['title'] === 'Notes' && $recentNotes->isNotEmpty()) 
                        <div class="flex-1 flex flex-col gap-3 overflow-y-auto pr-1.5 custom-scrollbar -mx-1.5 px-1.5">
                            @foreach($recentNotes as $note)
                                <a
                                    href="{{ route('projects.notes', ['project' => $project->project_id, 'note' => $note->note_id]) }}"
                                    wire:navigate
                                    class="flex flex-col gap-2 bg-card-bg border border-card-border p-4 rounded-lg group cursor-pointer hover:bg-card-hover hover:border-secondary-100 transition-all duration-200"
                                >
                                    <div class="flex items-center gap-2 min-w-0">
                                        <svg class="w-8 h-8 text-[#8C7558] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3.5h7.5L19 8v12.5a1 1 0 01-1 1H7a1 1 0 01-1-1V4.5a1 1 0 011-1z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3.5V8h5" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6M9 16.5h6" />
                                        </svg>
                                        <p class="text-app-title-1 leading-none text-[18px] text-text-80 truncate group-hover:text-secondary-200 transition-colors min-w-0">{{ $note->title }}</p>
                                    </div>

                                    @php
                                        // 1. Ubah tag block/pemisah (p, br, li, h1-h3, div) menjadi spasi agar kata tidak menempel
                                        $cleanBody = preg_replace('/<(p|br|h\d|li|div)[^>]*>/i', ' ', $note->body ?? '');

                                        // 2. Hapus semua tag HTML yang tersisa
                                        $cleanBody = strip_tags($cleanBody);

                                        // 3. Bersihkan spasi ganda yang berlebihan akibat proses sebelumnya
                                        $cleanBody = trim(preg_replace('/\s+/', ' ', $cleanBody));
                                    @endphp

                                    <p class="text-app-body-small text-text-70" style="display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden;">
                                        {{ $cleanBody ?: 'No content yet' }}
                                    </p>
                                </a>
                            @endforeach
                        </div>
                    @elseif($workspace['title'] === 'Character' && $recentCharacters->isNotEmpty())
                        <div class="flex-1 flex flex-col gap-2 overflow-y-auto custom-scrollbar -mx-1.5 px-1">
                            @foreach($recentCharacters as $character)
                                <a
                                    href="{{ route('projects.character.show', ['project' => $project->project_id, 'character' => $character->character_id]) }}"
                                    wire:navigate
                                    class="flex items-center gap-3 bg-card-bg border border-card-border p-4 rounded-lg group cursor-pointer hover:bg-card-hover hover:border-secondary-100 transition-all duration-200"
                                >
                                    <div class="w-18 h-18 shrink-0 rounded-lg bg-brand-200 overflow-hidden flex items-center justify-center">
                                        @if($character->image_path)
                                            <img src="{{ Storage::url($character->image_path) }}" class="w-full h-full object-cover">
                                        @else
                                            <x-icons.default-avatar class="w-full h-full text-[#A08866]" />
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1 gap-1 py-1">
                                        <p class="text-app-title-1 leading-none text-[18px] text-text-80 truncate group-hover:text-secondary-200 transition-colors">{{ $character->nick_name }}</p>
                                        @if($character->hashtags->isNotEmpty())
                                            @php
                                                $visibleTags = $character->hashtags->take(1);
                                                $remainingTagCount = $character->hashtags->count() - $visibleTags->count();
                                            @endphp
                                            <div class="mt-1.5">
                                                <div class="flex items-center gap-1 flex-nowrap overflow-hidden">
                                                    <span class="text-[11px] text-text-80 shrink-0">Tags :</span>
                                                    @foreach($visibleTags as $tag)
                                                        <span class="px-2 py-0.5 rounded-full bg-brand-100 text-[11px] text-text-70 shrink-0 truncate max-w-[80px]">{{ $tag->name }}</span>
                                                    @endforeach
                                                </div>
                                                @if($remainingTagCount > 0)
                                                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full bg-brand-150 text-[11px] text-text-70">+{{ $remainingTagCount }} tags</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="flex-1 flex flex-col items-center justify-center gap-4">
                            @php $iconPath = 'icons.'.$workspace['icon']; @endphp
                            <div class="w-28 h-28">
                                <x-dynamic-component :component="$iconPath" class="w-full h-full" />
                            </div>
                            <p class="mx-auto text-app-desc-feature text-center text-secondary-100">{{ $workspace['desc'] }}</p>
                        </div>
                    @endif

                    @if($workspace['route'])
                        <a href="{{ route($workspace['route'], ['project' => $project->project_id]) }}" wire:navigate 
                            class="w-full py-3 mt-4 mx-1 border-2 border-secondary-100 bg-transparent rounded-lg text-app-feature text-secondary-200 hover:bg-secondary-100/10 transition-colors text-center block" style="width: calc(100% - 8px);">
                            {{ $workspace['btn'] }}
                        </a>
                    @else
                        <button class="w-full py-3 mt-4 mx-1 border-2 border-secondary-100 bg-transparent rounded-lg text-app-feature text-secondary-200 hover:bg-secondary-100/10 transition-colors text-center block" style="width: calc(100% - 8px);">
                            {{ $workspace['btn'] }}
                        </button>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
