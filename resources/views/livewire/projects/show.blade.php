<?php

use Livewire\Volt\Component;
use App\Models\Project;
use App\Models\Note;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use App\Traits\HandlesFileUpload;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads, HandlesFileUpload;

    public Project $project;

    // Variabel terpisah untuk menjamin data tersimpan aman
    public string $title = '';
    public string $synopsis = '';

    public $newCategoryName = '';
    public $cover_image;

    //Properti untuk kustomisasi ikon
    public string $icon_type = 'default';
    public string $icon_emoji = '';
    public $icon_image;

    // Daftar 10 notes yang terakhir diedit, untuk ditampilkan di card Notes
    public $recentNotes = [];

    // Daftar karakter untuk ditampilkan di card Character
    public $recentCharacters = [];

    // Daftar chapter untuk ditampilkan
    public $recentChapters = [];

    public function mount(Project $project) {
        $this->project = $project;
        $this->title = $project->title;
        $this->synopsis = $project->synopsis ?? '';

        // Touch the project so it moves to the top of recent projects
        $this->project->touch();
        $this->dispatch('project-updated');

        $this->recentNotes = Note::where('project_id', $project->project_id)
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        $this->recentCharacters = $project->characters()
            ->with('hashtags')
            ->orderByDesc('updated_at')
            ->get();

        $this->recentChapters = $project->chapterCards()
            ->with(['tags', 'manuscript'])
            ->orderBy('order_index')
            ->get();

        $this->icon_type = $project->icon_type ?? 'default';
        if ($this->icon_type === 'emoji') {
            $this->icon_emoji = $project->icon;
        }
    }

    public function saveTitle() {
        $this->title = trim($this->title) ?: 'Untitled Project';
        $this->project->update(['title' => $this->title]);
        $this->dispatch('project-updated');
    }

    public function saveSynopsis() {
        $this->project->update(['synopsis' => trim($this->synopsis)]);
        $this->dispatch('project-updated');
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
        $this->validate([
            'cover_image' => 'image|max:5120'
        ], [
            'cover_image.max' => 'The selected image is too large. The maximum allowed file size is 5MB.',
            'cover_image.image' => 'The selected file type is not supported. Please upload an image.',
        ]);
        
        $path = $this->replaceImage($this->cover_image, $this->project->cover_image_path, 'covers');
        $this->project->update(['cover_image_path' => $path]);
        $this->cover_image = null;
        $this->project->refresh();
    }

    public function deleteCover() {
        $this->deleteImage($this->project->cover_image_path);
        $this->project->update(['cover_image_path' => null]);
        $this->project->refresh();
    }

    public function archiveProject() {
        $this->project->update(['archived_at' => now()]);
        $this->dispatch('project-updated');
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

    #[On('project-pinned-updated')]
    public function refreshPinState() {
        $this->project->refresh();
    }

    // Menyimpan ikon langsung dari klik emoji di UI
    public function setEmoji($emoji)
    {
        $this->icon_type = 'emoji';
        $this->icon_emoji = $emoji;
        
        if ($this->project->icon_type === 'image' && $this->project->icon) {
            $this->deleteImage($this->project->icon);
        }
        
        $this->project->update([
            'icon_type' => 'emoji',
            'icon'      => $emoji,
        ]);

        $this->dispatch('project-updated');
    }

    // Menyimpan ikon dari tab Upload Image
    public function saveIcon()
    {
        $this->validate([
            'icon_image' => 'nullable|file|mimes:jpeg,png,jpg,svg,webp|max:2048',
        ], [
            'icon_image.max' => 'The selected image is too large. The maximum allowed file size is 2MB.',
            'icon_image.mimes' => 'The selected file type is not supported. Please upload a JPG, PNG, SVG, or WEBP.',
        ]);

        if ($this->icon_image) {
            $oldPath = ($this->project->icon_type === 'image') ? $this->project->icon : null;
            $path = $this->replaceImage($this->icon_image, $oldPath, 'project-icons');
            
            $this->project->update([
                'icon_type' => 'image',
                'icon'      => $path,
            ]);

            $this->icon_type = 'image';
            $this->icon_image = null;

            $this->dispatch('project-updated');
            $this->dispatch('close-icon-picker');
        }
    }

    // Menghapus ikon (Kembali ke Default Book)
    public function removeIcon()
    {
        if ($this->project->icon_type === 'image' && $this->project->icon) {
            $this->deleteImage($this->project->icon);
        }
        
        $this->icon_type = 'default';
        $this->icon_emoji = '';
        $this->icon_image = null;
        
        $this->project->update([
            'icon_type' => 'default',
            'icon'      => null,
        ]);

        $this->dispatch('project-updated');
    }
}; ?>

<div>

    <div class="p-6 lg:p-10 max-w-7xl mx-auto">
        <x-breadcrumb :items="[
            ['label' => __('Dashboard'), 'url' => route('dashboard')],
            ['label' => $title, 'truncate' => true]
        ]" />

        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6 mb-16 items-stretch">

            @include('livewire.projects.partials.cover-section')

            @include('livewire.projects.partials.project-info')

        <div>
            <div class="flex items-center gap-6 mb-8">
                <h2 class="text-[28px] text-web-heading-2">{{ __('Workspace') }}</h2>
                <div class="flex-1 h-px bg-brand-150"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach([
                    ['id' => 'structure', 'title' => __('Structure'), 'icon' => 'no-structure', 'desc' => __('You Didn\'t Have Any Chapters!'), 'btn' => __('View Structure'), 'route' => 'projects.structure'],
                    ['id' => 'character', 'title' => __('Character'), 'icon' => 'no-character', 'desc' => __('You Didn\'t Have Any Characters!'), 'btn' => __('View Character'), 'route' => 'projects.characters'],
                    ['id' => 'notes', 'title' => __('Notes'), 'icon' => 'no-notes', 'desc' => __('You Didn\'t Have Any Notes!'), 'btn' => __('View Notes'), 'route' => 'projects.notes']
                ] as $workspace)

                <div class="bg-brand-100 rounded-md p-4 flex flex-col justify-between h-[396px] border border-brand-150">
                    <h3 class="text-app-heading-2 text-text-100 mb-4 px-1">{{ $workspace['title'] }}</h3>

                    @if($workspace['id'] === 'notes' && $recentNotes->isNotEmpty()) 
                        <div class="flex-1 flex flex-col gap-3 overflow-y-auto pr-1.5 custom-scrollbar -mx-1.5 px-1.5">
                            @foreach($recentNotes as $note)
                                <a
                                    href="{{ route('projects.notes', ['project' => $project->project_id, 'note' => $note->note_id]) }}"
                                    wire:navigate
                                    class="flex flex-col gap-2 bg-card-bg border border-card-border p-4 rounded-lg group cursor-pointer hover:bg-card-hover hover:border-secondary-100 transition-all duration-200"
                                >
                                    <div class="flex items-center gap-2 min-w-0">
                                        <svg class="w-8 h-8 text-secondary-250 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3.5h7.5L19 8v12.5a1 1 0 01-1 1H7a1 1 0 01-1-1V4.5a1 1 0 011-1z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3.5V8h5" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6M9 16.5h6" />
                                        </svg>
                                        <p class="text-app-title-1 leading-normal text-[18px] text-text-80 truncate group-hover:text-secondary-200 transition-colors min-w-0">{{ $note->title }}</p>
                                    </div>

                                    @php
                                        // 1. Ubah tag block/pemisah (p, br, li, h1-h3, div) menjadi spasi agar kata tidak menempel
                                        $cleanBody = preg_replace('/<(p|br|h\d|li|div)[^>]*>/i', ' ', $note->body ?? '');

                                        // 2. Hapus semua tag HTML yang tersisa
                                        $cleanBody = strip_tags($cleanBody);

                                        // 3. Bersihkan entitas HTML dan spasi ganda yang berlebihan
                                        $cleanBody = html_entity_decode($cleanBody, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                        $cleanBody = str_replace(["\xC2\xA0", '&nbsp;', '&#160;', '&amp;nbsp;'], ' ', $cleanBody);
                                        $cleanBody = trim(preg_replace('/\s+/u', ' ', $cleanBody));
                                    @endphp

                                    <p class="text-app-body-small text-subtext-100 line-clamp-3" style="display:-webkit-box; -webkit-box-orient:vertical; overflow:hidden;">
                                        {{ $cleanBody ?: __('No content yet') }}
                                    </p>
                                </a>
                            @endforeach
                        </div>
                    @elseif($workspace['id'] === 'character' && $recentCharacters->isNotEmpty())
                        <div class="flex-1 flex flex-col gap-2 overflow-y-auto custom-scrollbar -mx-1.5 px-1">
                            @foreach($recentCharacters as $character)
                                <a
                                    href="{{ route('projects.character.show', ['project' => $project->project_id, 'character' => $character->character_id]) }}"
                                    wire:navigate
                                    class="flex items-center gap-3 bg-card-bg border border-card-border p-4 rounded-lg group cursor-pointer hover:bg-card-hover hover:border-secondary-100 transition-all duration-200"
                                >
                                    <div class="w-18 h-18 shrink-0 rounded-lg bg-secondary-50 overflow-hidden flex items-center justify-center">
                                        @if($character->image_path)
                                            <img src="{{ Storage::url($character->image_path) }}" class="w-full h-full object-cover">
                                        @else
                                            <x-icons.default-avatar class="w-full h-full text-brand-200" />
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1 gap-1 py-1">
                                        <p class="text-app-title-1 leading-none text-[18px] text-text-80 truncate group-hover:text-secondary-200 transition-colors">{{ $character->nick_name }}</p>
                                            <div class="mt-1.5">
                                                @php
                                                    $visibleTags = $character->hashtags->take(1);
                                                    $remainingTagCount = $character->hashtags->count() - $visibleTags->count();
                                                @endphp
                                                <div class="flex items-center gap-1 flex-nowrap overflow-hidden">
                                                    <span class="text-app-body-small text-subtext-90 shrink-0">{{ __('Tags') }} :</span>
                                                    
                                                    @if($character->hashtags->isNotEmpty())
                                                        @foreach($visibleTags as $tag)
                                                            <span class="px-2 py-0.5 rounded-full bg-brand-100 text-app-caption text-text-70 shrink-0 truncate max-w-[80px]">
                                                                {{ $tag->name }}
                                                            </span>
                                                        @endforeach
                                                    @else
                                                        {{-- Empty State --}}
                                                        <span class="px-2 py-0.5 rounded border border-dashed border-brand-200 bg-card-hover text-app-caption text-secondary-100 shrink-0">
                                                            {{ __('No tags') }}
                                                        </span>
                                                    @endif
                                                </div>

                                                {{-- Remaining Tags Badge --}}
                                                @if($remainingTagCount > 0)
                                                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full bg-brand-150 text-app-caption text-text-70">
                                                        +{{ $remainingTagCount }} {{ __('tags') }}
                                                    </span>
                                                @endif
                                            </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @elseif($workspace['id'] === 'structure' && $recentChapters->isNotEmpty())
                        <div class="flex-1 flex flex-col gap-3 overflow-y-auto pr-1.5 custom-scrollbar -mx-1.5 px-1.5 pb-2">
                            @foreach($recentChapters as $chapter)
                                <a href="{{ route('projects.manuscript', ['project' => $chapter->project_id, 'chapterCard' => $chapter->chapter_card_id]) }}" 
                                   wire:navigate 
                                   class="flex flex-col bg-card-bg border border-1 border-card-border p-4 rounded-xl group cursor-pointer hover:bg-card-hover hover:border-secondary-150 hover:shadow-sm transition-all duration-200 shrink-0">
                                    
                                    <div class="text-app-desc-feature text-text-60 mb-1.5">
                                        {{ __('Chapter') }} {{ $chapter->order_index }}
                                    </div>

                                    <div class="flex items-center gap-3 mb-2.5">
                                        <x-icons.chapter-icon class="shrink-0 text-secondary-100 stroke-brand-50"/>
                                        <h4 class="text-[18px] text-app-title-1 leading-normal text-text-80 truncate transition-colors">
                                            {{ $chapter->title }}
                                        </h4>
                                    </div>

                                    <div class="flex items-center gap-1.5 mb-3 flex-wrap">
                                        <span class="text-app-body-small text-subtext-90 shrink-0">{{ __('Tags') }} :</span>
                                        @if($chapter->tags->isNotEmpty())
                                            @foreach($chapter->tags->take(2) as $tag)
                                                <span class="px-2 py-0.5 border border-brand-200 bg-card-hover rounded text-app-caption text-secondary-100">
                                                    {{ $tag->name }}
                                                </span>
                                            @endforeach
                                            @if($chapter->tags->count() > 2)
                                                <span class="px-1 text-app-caption text-secondary-100">
                                                    +{{ $chapter->tags->count() - 2 }} {{ __('more') }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="px-2 py-0.5 rounded border border-dashed border-brand-200 bg-card-hover text-app-caption text-secondary-100">
                                                {{ __('No tags') }}
                                            </span>
                                        @endif
                                    </div>

                                    @php
                                        $summaryText = $chapter->summary;
                                        if (empty(trim($summaryText ?? '')) && $chapter->manuscript && $chapter->manuscript->isNotEmpty()) {
                                            $firstDraftContent = $chapter->manuscript->first()->content ?? '';
                                            $html = preg_replace('/<(br|\/p|\/div|\/h[1-6]|\/li|\/tr|\/blockquote|\/pre)[^>]*>/i', "\n", $firstDraftContent);
                                            $cleanText = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                            $cleanText = str_replace(["\xC2\xA0", '&nbsp;', '&#160;', '&amp;nbsp;'], ' ', $cleanText);
                                            $cleanText = preg_replace('/[ \t]+/u', ' ', trim($cleanText));
                                            if ($cleanText !== '') {
                                                if (preg_match_all('/[^.!?\r\n]+[.!?]?/', $cleanText, $matches) && !empty($matches[0])) {
                                                    $sents = [];
                                                    foreach ($matches[0] as $m) {
                                                        $c = trim($m);
                                                        if ($c !== '') $sents[] = $c;
                                                    }
                                                    if (!empty($sents)) {
                                                        $summaryText = implode(' ', array_slice($sents, 0, 2));
                                                    } else {
                                                        $summaryText = $cleanText;
                                                    }
                                                } else {
                                                    $summaryText = $cleanText;
                                                }
                                                $chapter->update(['summary' => trim($summaryText)]);
                                            }
                                        }
                                    @endphp
                                    <p class="text-app-body-small text-subtext-100 mb-4 line-clamp-2">
                                        {{ !empty(trim($summaryText ?? '')) ? $summaryText : __('No summary available for this chapter yet.') }}
                                    </p>

                                    <div class="flex flex-wrap items-center justify-between gap-x-2 gap-y-2 mt-auto pt-3">
                                        <div class="flex items-center gap-2 text-subtext-90 whitespace-nowrap shrink-0">
                                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"/></svg>
                                            <span class="text-app-desc-feature">{{ $chapter->manuscript ? number_format($chapter->manuscript->count()) : 0 }} {{ __('Drafts') }}</span>
                                        </div>

                                        <span @class([
                                            'text-app-caption px-2 py-1 rounded-md flex items-center gap-1.5 shadow-sm whitespace-nowrap shrink-0',
                                            'bg-warning-100/50' => $chapter->status === 'In Progress',
                                            'bg-success-100/50' => $chapter->status === 'Completed',
                                            'bg-brand-150' => !in_array($chapter->status, ['In Progress', 'Completed'])
                                        ])>
                                            <x-icons.chapter-status :status="$chapter->status" class="w-3.5 h-3.5 shrink-0" />
                                            {{ $chapter->status ?? __('In Progress') }}
                                        </span>
                                        
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
                            <p class="mx-auto text-app-desc-feature text-center text-secondary-100">{{ __($workspace['desc']) }}</p>
                        </div>
                    @endif

                    @if($workspace['route'])
                        <a href="{{ route($workspace['route'], ['project' => $project->project_id]) }}" wire:navigate 
                            class="w-full py-3 mt-4 mx-1 border-2 border-secondary-200/70 bg-transparent rounded-lg text-app-feature text-secondary-200 hover:bg-secondary-100/10 transition-colors text-center block" style="width: calc(100% - 8px);">
                            {{ __($workspace['btn']) }}
                        </a>
                    @else
                        <button class="w-full py-3 mt-4 mx-1 border-2 border-secondary-100 bg-transparent rounded-lg text-app-feature text-secondary-200 hover:bg-secondary-100/10 transition-colors text-center block" style="width: calc(100% - 8px);">
                            {{ __($workspace['btn']) }}
                        </button>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
    
    <x-confirm-dialog
        eventName="open-archive-project-dialog"
        title="{{ __('Archive this Project?') }}"
        description="{{ __('Are you sure you want to archive this project? You can restore it from the Archive page.') }}"
        confirmText="{{ __('Yes, Archive') }}"
        cancelText="{{ __('Cancel') }}"
        submitAction="archiveProject"
        iconColor="text-warning-100"
        iconBg="bg-warning-100/10"
        btnColor="bg-warning-100 hover:bg-warning-100/90 text-white"
    >
        <x-slot:icon>
            <x-icons.archive class="w-12 h-12" />
        </x-slot:icon>
    </x-confirm-dialog>
</div>
