<?php
use Livewire\Volt\Component;
use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public Project $project;

    // Variabel terpisah untuk menjamin data tersimpan aman
    public string $title = '';
    public string $synopsis = '';

    public $newCategoryName = '';
    public $cover_image;

    public function mount(Project $project) {
        $this->project = $project;
        $this->title = $project->title;
        $this->synopsis = $project->synopsis ?? '';
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
                'name' => trim($this->newCategoryName)
            ]);
            $this->newCategoryName = '';
            $this->project->load('categories');
        }
    }

    public function renameCategory($id, $newName) {
        $category = $this->project->categories()->find($id);
        if ($category && trim($newName) !== '') {
            $category->update(['name' => trim($newName)]);
        }
        $this->project->load('categories');
    }

    public function deleteCategory($id) {
        $this->project->categories()->find($id)?->delete();
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
            <div class="flex items-center gap-3 text-app-heading-2 text-text-80">
                <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-secondary-200 transition-colors">Dashboard</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-text-100 font-semibold truncate">{{ $title }}</span>
            </div>
            <x-logo class="h-8 w-auto text-text-100" />
        </header>

        <div class="flex flex-col lg:flex-row gap-8 lg:gap-12 mb-16 items-stretch">

            <div
                x-data="{ hoverCover: false }"
                @mouseover="hoverCover = true"
                @mouseleave="hoverCover = false"
                class="relative w-full lg:w-[320px] xl:w-[360px] shrink-0 aspect-[1/1.45] z-10"
            >
                @if($project->cover_image_path)
                    <img src="{{ Storage::url($project->cover_image_path) }}" class="absolute inset-y-0 left-0 right-3 w-[calc(100%-12px)] h-full object-cover rounded-l-md rounded-r-xl shadow-md z-20" />
                    <div class="absolute inset-y-0 left-0 w-8 bg-gradient-to-r from-black/50 via-black/10 to-transparent z-20 pointer-events-none mix-blend-multiply rounded-l-md"></div>
                    <div class="absolute top-[2%] bottom-[2%] right-1.5 w-3 bg-[#E8E3D9] border-y border-r border-[#C4B7A3] rounded-r-md z-10"></div>
                    <div class="absolute inset-y-0 right-0 w-3 bg-[#8C7558] rounded-r-md z-0 shadow-lg"></div>
                @else
                    <x-default-project class="absolute inset-0 w-full h-full text-[#B69F78]" preserveAspectRatio="none" />
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

                <div wire:loading wire:target="cover_image" class="absolute inset-y-0 left-0 right-3 w-[calc(100%-12px)] bg-[#F5EFE9]/60 backdrop-blur-sm z-40 flex items-center justify-center rounded-l-md rounded-r-xl">
                    <svg class="animate-spin h-8 w-8 text-secondary-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </div>
            </div>

            <div class="flex-1 min-w-0 bg-[#F5EFE9] border border-[#E8E1D5] p-10 pb-16 rounded-xl shadow-sm relative flex flex-col">

                <div class="flex justify-between items-start mb-8">
                    <div class="flex-1 min-w-0 mr-4">
                        <x-icons.sidebar-book class="w-8 h-8 text-[#A08866] mb-3" />

                        @php
                            $titleLen = strlen($title);
                            $dtlTitleSize = 'text-[32px]';
                            if ($titleLen >= 25) { $dtlTitleSize = 'text-[22px]'; } elseif ($titleLen >= 15) { $dtlTitleSize = 'text-[26px]'; }
                        @endphp

                        <div x-data="{ editingTitle: false, hoverTitle: false, localTitle: @entangle('title') }" @mouseover="hoverTitle = true" @mouseleave="hoverTitle = false" class="flex items-center gap-3 group">
                            <h1
                                x-show="!editingTitle"
                                @dblclick="editingTitle = true; setTimeout(() => $refs.titleInput.focus(), 50)"
                                class="{{ $dtlTitleSize }} font-merriweather font-medium text-[#2C2C2C] transition-colors leading-tight cursor-pointer select-none group-hover:text-secondary-200 truncate"
                            >
                                <span x-text="localTitle || 'Untitled Project'"></span>
                            </h1>
                            <button x-show="hoverTitle && !editingTitle" @click="editingTitle = true; setTimeout(() => $refs.titleInput.focus(), 50)" class="text-[#A08866] hover:text-secondary-200 transition-colors shrink-0">
                                <x-icons.rename class="w-5 h-5" />
                            </button>

                            <input
                                x-show="editingTitle"
                                x-model="localTitle"
                                x-ref="titleInput"
                                @click.outside="if(editingTitle) { $wire.saveTitle(); editingTitle = false; }"
                                @keydown.enter="$wire.saveTitle(); editingTitle = false"
                                @keydown.escape="editingTitle = false; localTitle = '{{ addslashes($project->title) }}'"
                                class="{{ $dtlTitleSize }} font-merriweather font-medium text-[#2C2C2C] bg-transparent border-b-2 border-[#D5C6A9] outline-none w-full focus:border-[#A08866] focus:ring-0 px-0 py-1"
                            />
                        </div>
                        <p class="text-[15px] text-[#7A7A7A] mt-2">from <span class="font-semibold text-[#4A4A4A]">Sailor's Version Series</span></p>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <button class="flex items-center gap-2 border border-[#D5C6A9] bg-transparent px-3 py-1.5 rounded-lg text-[13px] font-medium text-[#4A4A4A] hover:bg-[#EAE1D5] transition-colors">
                            <x-icons.archive class="w-4 h-4" /> Move To Archive
                        </button>
                        <button class="text-[#8C7558] hover:text-[#5E4C38] transition-colors">
                            <x-icons.bookmark class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <div x-data="{ addingCat: false }" class="mb-6">
                    <div class="flex items-center gap-2 mb-3">
                        <x-icons.category class="w-4 h-4 text-[#8C7558]" />
                        <span class="text-[11px] font-bold text-[#4A4A4A] uppercase tracking-[0.15em]">Categories</span>
                    </div>

                    <div class="flex flex-wrap gap-2 items-center">
                        @foreach($project->categories as $category)
                            <div x-data="{ editingCat: false, hoverCat: false }" @mouseover="hoverCat = true" @mouseleave="hoverCat = false" class="relative group">
                                <div x-show="!editingCat" class="px-3 py-1.5 rounded-md bg-[#EAE1D5] text-[13px] text-[#4A4A4A] font-medium flex gap-2 items-center border border-transparent group-hover:border-[#D5C6A9] transition-colors">
                                    <span @dblclick="editingCat = true; setTimeout(() => $refs.editCat{{ $category->category_id }}.focus(), 50)" class="cursor-pointer select-none">
                                        {{ $category->name }}
                                    </span>
                                    <button wire:click="deleteCategory('{{ $category->category_id }}')" x-show="hoverCat" class="text-[#A08866] hover:text-[#E64C4C] transition-colors">
                                        <x-icons.delete class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                                <input x-show="editingCat" x-ref="editCat{{ $category->category_id }}" value="{{ $category->name }}" @keyup.enter="editingCat = false; $wire.renameCategory('{{ $category->category_id }}', $el.value)" @blur="editingCat = false; $wire.renameCategory('{{ $category->category_id }}', $el.value)" class="w-24 text-[13px] text-[#2C2C2C] bg-transparent border-b-2 border-[#D5C6A9] outline-none px-1 py-0.5 focus:border-[#A08866]" />
                            </div>
                        @endforeach

                        <button x-show="!addingCat" @click="addingCat = true; setTimeout(() => $refs.catInput.focus(), 50)" class="px-2 py-1.5 rounded-md border border-[#D5C6A9] text-[#8C7558] hover:bg-[#EAE1D5] flex items-center justify-center transition-colors bg-transparent">
                            <x-icons.add class="w-4 h-4" />
                        </button>

                        <div x-show="addingCat" class="flex items-center gap-2">
                            <input type="text" x-model="$wire.newCategoryName" x-ref="catInput" @keyup.enter="addingCat = false; $wire.addCategory()" @click.outside="addingCat = false; $wire.newCategoryName = ''" placeholder="Category..." class="w-28 text-[13px] bg-transparent border-b-2 border-[#D5C6A9] outline-none px-1 py-0.5 text-[#2C2C2C] focus:border-[#A08866]"/>
                        </div>
                    </div>
                </div>

                <div class="h-px bg-[#D5C6A9]/60 w-full mb-6"></div>

                <div x-data="{
                    editingSyn: false,
                    hoverSyn: false,
                    showMore: false,
                    isOverflowing: false,
                    localSyn: @entangle('synopsis'),
                    checkOverflow() {
                        if(this.$refs.synText) {
                            // Cek apakah konten melebihi batas 150px
                            this.isOverflowing = this.$refs.synText.scrollHeight > 150;
                        }
                    }
                }"
                x-init="$watch('localSyn', () => $nextTick(() => checkOverflow())); setTimeout(() => checkOverflow(), 200)"
                @resize.window="checkOverflow()"
                class="w-full"> <div @mouseover="hoverSyn = true" @mouseleave="hoverSyn = false" class="flex items-center gap-3 mb-1">
                        <span class="text-[16px] font-bold text-[#2C2C2C]">Synopsis</span>
                        <button x-show="hoverSyn && !editingSyn" @click="editingSyn = true; setTimeout(() => $refs.synInput.focus(), 50)" class="text-[#A08866] hover:text-secondary-200 transition-colors">
                            <x-icons.rename class="w-4 h-4" />
                        </button>
                    </div>

                    <div x-show="!editingSyn" class="relative group w-full">
                        <div
                            x-ref="synText"
                            @dblclick="editingSyn = true; setTimeout(() => $refs.synInput.focus(), 50)"
                            class="text-[15px] text-[#4A4A4A] leading-[1.7] whitespace-pre-wrap select-none cursor-pointer w-full transition-all duration-300"
                            :class="showMore ? 'max-h-[300px] overflow-y-auto pr-3 custom-scrollbar' : 'max-h-[150px] overflow-hidden'"
                        >
                            <div x-show="localSyn.trim() !== ''" x-text="localSyn"></div>
                            <div x-show="localSyn.trim() === ''" class="text-[#A08866]/60 italic font-medium">Write your synopsis here!</div>
                        </div>

                        <div x-show="isOverflowing && !showMore" class="absolute bottom-0 left-0 w-full h-16 bg-gradient-to-t from-[#F5EFE9] via-[#F5EFE9]/90 to-transparent pointer-events-none"></div>
                    </div>

                    <div x-show="isOverflowing && !editingSyn" class="mt-2 w-full flex justify-center">
                        <button
                            @click="showMore = !showMore; if(!showMore) $nextTick(() => checkOverflow())"
                            class="text-[13px] font-bold text-[#2C2C2C] hover:text-secondary-200 flex items-center gap-1 z-10 px-4 py-1 rounded-full"
                        >
                            <svg class="w-4 h-4 transition-transform" :class="showMore ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            <span x-text="showMore ? 'Show Less' : 'Show More'"></span>
                        </button>
                    </div>

                    <textarea
                        x-show="editingSyn"
                        x-model="localSyn"
                        x-ref="synInput"
                        @click.outside="if(editingSyn) { $wire.saveSynopsis(); editingSyn = false; }"
                        @keydown.ctrl.enter="$wire.saveSynopsis(); editingSyn = false"
                        @keydown.escape="editingSyn = false; localSyn = `{{ addslashes($project->synopsis ?? '') }}`"
                        class="w-full mt-2 min-h-[150px] text-[15px] text-[#2C2C2C] leading-[1.7] bg-transparent border-2 border-[#D5C6A9] rounded-md outline-none resize-none p-4 focus:border-[#A08866] transition-colors custom-scrollbar"
                    ></textarea>
                </div>

                <div class="absolute bottom-8 right-10 text-[12px] font-medium text-[#7A7A7A]">
                    Last Edited {{ $project->updated_at->diffForHumans() }}
                </div>
            </div>
        </div>

        <div>
            <div class="flex items-center gap-6 mb-8">
                <h2 class="text-[28px] font-merriweather text-[#2C2C2C]">Workspace</h2>
                <div class="flex-1 h-px bg-[#D5C6A9]"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach([
                    ['title' => 'Structure', 'icon' => 'no-structure', 'desc' => 'You Didn\'t Have Any Chapters!', 'btn' => 'View Structure', 'url' => route('projects.structure', $project->project_id)],
                    ['title' => 'Character', 'icon' => 'no-character', 'desc' => 'You Didn\'t Have Any Characters!', 'btn' => 'View Character', 'url' => '#'],
                    ['title' => 'Notes', 'icon' => 'no-notes', 'desc' => 'You Didn\'t Have Any Notes!', 'btn' => 'View Notes', 'url'=> '#']
                ] as $workspace)

                <div class="bg-[#EAE1D5] rounded-xl p-8 flex flex-col justify-between h-[360px] shadow-sm border border-brand-100 hover:shadow-md transition-shadow">
                    <h3 class="text-xl font-bold text-text-100 mb-4">{{ $workspace['title'] }}</h3>

                    <div class="flex-1 flex flex-col items-center justify-center gap-6 opacity-60">
                        @php $iconPath = 'icons.'.$workspace['icon']; @endphp
                        <div class="w-32 h-32 text-text-80">
                            <x-dynamic-component :component="$iconPath" class="w-full h-full" />
                        </div>
                        <p class="text-sm font-semibold text-text-80">{{ $workspace['desc'] }}</p>
                    </div>

                    <a href="{{ $workspace['url'] }}" wire:navigate class="w-full py-3 mt-4 text-center border border-[#D5C6A9] bg-transparent rounded-lg text-[14px] font-bold text-[#4A4A4A] hover:bg-[#DFD5C5] transition-colors">
                        {{ $workspace['btn'] }}
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
