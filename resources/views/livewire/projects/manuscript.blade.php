<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use App\Models\Project;
use App\Models\ChapterCard;
use App\Models\Manuscript;
use App\Models\Character;
use App\Models\Tag;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public Project $project;
    public ChapterCard $chapterCard;
    public ?string $activeDraftId = null;
    public string $editorBody = '';
    public $uploadFile;
    public $coverUpload;

    public function mount(Project $project, ChapterCard $chapterCard): void
    {
        if ($chapterCard->project_id !== $project->project_id) {
            abort(404);
        }

        $this->project = $project;
        $this->chapterCard = $chapterCard;

        // Select the first draft, or create one if none exist
        $draft = $chapterCard->manuscript()->orderBy('created_at')->first();

        if (!$draft) {
            $draft = Manuscript::create([
                'chapter_card_id' => $chapterCard->chapter_card_id,
                'title' => 'Draft 1',
                'content' => '',
                'word_count' => 0,
            ]);
        }

        $this->activeDraftId = $draft->manuscript_id;
        $this->editorBody = $draft->content ?? '';
    }

    // -----------------------------------------------------------------------
    // Draft Actions
    // -----------------------------------------------------------------------

    public function selectDraft(string $draftId): void
    {
        $this->saveCurrentContent();

        $draft = Manuscript::where('manuscript_id', $draftId)
            ->where('chapter_card_id', $this->chapterCard->chapter_card_id)
            ->first();

        if (!$draft) return;

        $this->activeDraftId = $draftId;
        $this->editorBody = $draft->content ?? '';
    }

    public function addDraft(): void
    {
        $this->saveCurrentContent();

        $count = Manuscript::where('chapter_card_id', $this->chapterCard->chapter_card_id)->count();

        $draft = Manuscript::create([
            'chapter_card_id' => $this->chapterCard->chapter_card_id,
            'title' => 'Draft ' . ($count + 1),
            'content' => '',
            'word_count' => 0,
        ]);

        $this->activeDraftId = $draft->manuscript_id;
        $this->editorBody = '';
        $this->chapterCard->touch();
        $this->chapterCard->refresh();
    }

    public function renameDraft(string $draftId, string $newTitle): void
    {
        $draft = Manuscript::where('manuscript_id', $draftId)
            ->where('chapter_card_id', $this->chapterCard->chapter_card_id)
            ->first();

        if (!$draft) return;

        $trimmed = trim($newTitle);
        if ($trimmed === '') return;

        $draft->update(['title' => $trimmed]);
        $this->chapterCard->touch();
        $this->chapterCard->refresh();
    }

    public function deleteDraft(string $draftId): void
    {
        $drafts = Manuscript::where('chapter_card_id', $this->chapterCard->chapter_card_id)->get();

        if ($drafts->count() <= 1) return;

        $draft = $drafts->firstWhere('manuscript_id', $draftId);
        if (!$draft) return;

        $wasActive = ($this->activeDraftId === $draftId);
        $draft->delete();

        if ($wasActive) {
            $next = Manuscript::where('chapter_card_id', $this->chapterCard->chapter_card_id)
                ->orderBy('created_at')
                ->first();

            if ($next) {
                $this->activeDraftId = $next->manuscript_id;
                $this->editorBody = $next->content ?? '';
            }
        }
        $this->chapterCard->touch();
        $this->chapterCard->refresh();
    }

    public function moveDraft(string $draggedId, string $targetId): void
    {
        if ($draggedId === $targetId) return;

        $drafts = Manuscript::where('chapter_card_id', $this->chapterCard->chapter_card_id)
            ->orderBy('created_at')
            ->get();

        $ids = $drafts->pluck('manuscript_id')->toArray();
        $fromIdx = array_search($draggedId, $ids);
        $toIdx = array_search($targetId, $ids);

        if ($fromIdx !== false && $toIdx !== false) {
            array_splice($ids, $fromIdx, 1);
            array_splice($ids, $toIdx, 0, $draggedId);

            $baseTime = now()->subMinutes(count($ids));
            foreach ($ids as $index => $id) {
                Manuscript::where('manuscript_id', $id)
                    ->update(['created_at' => (clone $baseTime)->addSeconds($index * 10)]);
            }
            $this->chapterCard->touch();
            $this->chapterCard->refresh();
        }
    }

    // -----------------------------------------------------------------------
    // Editor Actions
    // -----------------------------------------------------------------------

    public function updateContent(string $html): void
    {
        $this->editorBody = $html;
        $this->saveCurrentContent();
    }

    public function saveCurrentContent(): void
    {
        if (!$this->activeDraftId) return;

        $text = strip_tags($this->editorBody);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', trim($text));
        $wordCount = $text === '' ? 0 : count(preg_split('/\s+/', $text));

        Manuscript::where('manuscript_id', $this->activeDraftId)
            ->where('chapter_card_id', $this->chapterCard->chapter_card_id)
            ->update([
                'content' => $this->editorBody,
                'word_count' => $wordCount,
            ]);

        $firstDraft = Manuscript::where('chapter_card_id', $this->chapterCard->chapter_card_id)
            ->orderBy('created_at')
            ->first();

        if ($firstDraft && $firstDraft->manuscript_id === $this->activeDraftId) {
            $html = $this->editorBody ?? '';
            $html = preg_replace('/<(br|\/p|\/div|\/h[1-6]|\/li|\/tr|\/blockquote|\/pre)[^>]*>/i', "\n", $html);
            $cleanText = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
            $cleanText = preg_replace('/[ \t]+/', ' ', trim($cleanText));

            if ($cleanText !== '') {
                $extracted = $cleanText;
                if (preg_match_all('/[^.!?\r\n]+[.!?]?/', $cleanText, $matches) && !empty($matches[0])) {
                    $sents = [];
                    foreach ($matches[0] as $m) {
                        $c = trim($m);
                        if ($c !== '') $sents[] = $c;
                    }
                    if (!empty($sents)) {
                        $extracted = implode(' ', array_slice($sents, 0, 2));
                    }
                }
                $this->chapterCard->update(['summary' => trim($extracted)]);
            } elseif ($cleanText === '' && empty(trim($this->chapterCard->summary ?? ''))) {
                $this->chapterCard->update(['summary' => null]);
            }
        }

        $this->chapterCard->touch();
        $this->chapterCard->refresh();
    }

    public function saveUploadedFile()
    {
        if ($this->uploadFile) {
            $path = $this->uploadFile->store('manuscript_attachments', 'public');
            $this->uploadFile = null;
            return asset('storage/' . $path);
        }
        return null;
    }

    // -----------------------------------------------------------------------
    // Chapter Details Panel Actions
    // -----------------------------------------------------------------------

    public function updateStatus(string $status): void
    {
        $allowed = ['In Progress', 'Completed'];
        if (in_array($status, $allowed)) {
            $this->chapterCard->update(['status' => $status]);
            $this->chapterCard->refresh();
        }
    }

    public function updatedCoverUpload(): void
    {
        $this->validate([
            'coverUpload' => 'image|max:10240',
        ]);

        if ($this->coverUpload) {
            if ($this->chapterCard->cover_image_path) {
                Storage::disk('public')->delete($this->chapterCard->cover_image_path);
            }
            $path = $this->coverUpload->store('chapter_covers', 'public');
            $this->chapterCard->update(['cover_image_path' => $path]);
            $this->coverUpload = null;
            $this->chapterCard->refresh();
        }
    }

    public function detachCoverImage(): void
    {
        if ($this->chapterCard->cover_image_path) {
            Storage::disk('public')->delete($this->chapterCard->cover_image_path);
            $this->chapterCard->update(['cover_image_path' => null]);
            $this->chapterCard->refresh();
        } elseif ($this->project->cover_image_path) {
            Storage::disk('public')->delete($this->project->cover_image_path);
            $this->project->update(['cover_image_path' => null]);
            $this->project->refresh();
        }
    }

    public function renameChapter(string $newTitle): void
    {
        $trimmed = trim($newTitle);
        if ($trimmed !== '') {
            $this->chapterCard->update(['title' => $trimmed]);
            $this->chapterCard->refresh();
        }
    }

    public function addTag(string $tagName): void
    {
        $trimmed = trim($tagName);
        if ($trimmed === '') return;

        $tag = Tag::firstOrCreate(['name' => strtolower($trimmed)]);
        $this->chapterCard->tags()->syncWithoutDetaching([$tag->id]);
        $this->chapterCard->touch();
        $this->chapterCard->refresh();
        $this->chapterCard->load('tags');
    }

    public function removeTag(int $tagId): void
    {
        $this->chapterCard->tags()->detach($tagId);
        $this->chapterCard->touch();
        $this->chapterCard->refresh();
        $this->chapterCard->load('tags');
    }

    public function attachCharacter(string $characterId): void
    {
        $this->chapterCard->characters()->syncWithoutDetaching([$characterId]);
        $this->chapterCard->touch();
        $this->chapterCard->refresh();
        $this->chapterCard->load('characters');
    }

    public function detachCharacter(string $characterId): void
    {
        $this->chapterCard->characters()->detach($characterId);
        $this->chapterCard->touch();
        $this->chapterCard->refresh();
        $this->chapterCard->load('characters');
    }

    public function updateSummary(string $newSummary): void
    {
        $this->chapterCard->update(['summary' => trim($newSummary)]);
        $this->chapterCard->refresh();
    }

    public function with(): array
    {
        $this->chapterCard->refresh();
        $drafts = Manuscript::where('chapter_card_id', $this->chapterCard->chapter_card_id)
            ->orderBy('created_at')
            ->get();

        $activeDraft = $this->activeDraftId
            ? $drafts->firstWhere('manuscript_id', $this->activeDraftId)
            : null;

        $this->chapterCard->load(['tags', 'characters']);

        $projectCharacters = Character::where('project_id', $this->project->project_id)
            ->orderBy('full_name')
            ->get();

        $displaySummary = $this->chapterCard->summary;
        if (empty(trim($displaySummary ?? '')) && $drafts->isNotEmpty()) {
            $contentSource = $drafts->first()->content ?? '';
            $html = $contentSource ?? '';
            $html = preg_replace('/<(br|\/p|\/div|\/h[1-6]|\/li|\/tr|\/blockquote|\/pre)[^>]*>/i', "\n", $html);
            $text = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
            $text = preg_replace('/[ \t]+/', ' ', trim($text));

            if ($text !== '') {
                if (preg_match_all('/[^.!?\r\n]+[.!?]?/', $text, $matches) && !empty($matches[0])) {
                    $sentences = [];
                    foreach ($matches[0] as $match) {
                        $cleaned = trim($match);
                        if ($cleaned !== '') {
                            $sentences[] = $cleaned;
                        }
                    }
                    if (!empty($sentences)) {
                        $displaySummary = implode(' ', array_slice($sentences, 0, 2));
                    } else {
                        $displaySummary = $text;
                    }
                } else {
                    $displaySummary = $text;
                }
                $this->chapterCard->update(['summary' => trim($displaySummary)]);
            }
        }

        return [
            'drafts' => $drafts,
            'activeDraft' => $activeDraft,
            'projectCharacters' => $projectCharacters,
            'displaySummary' => $displaySummary ?: 'No summary available for this chapter yet.',
        ];
    }
}; ?>

<div
    x-data="{
        showDetailPanel: true,
        renamingDraftId: null,
        renameValue: '',
        deletingDraftId: null,

        startRenameDraft(id, currentTitle) {
            this.renamingDraftId = id;
            this.renameValue = currentTitle;
            this.$nextTick(() => {
                const el = document.getElementById('draft-rename-' + id);
                if (el) { el.focus(); el.select(); }
            });
        },
        commitRenameDraft(id) {
            if (this.renameValue.trim() !== '') {
                $wire.renameDraft(id, this.renameValue);
            }
            this.renamingDraftId = null;
        },
        confirmDeleteDraft(id) {
            this.deletingDraftId = id;
            this.$dispatch('open-delete-draft-dialog', { id: id });
        }
    }"
    class="h-full"
>
    <style>
        [x-cloak] { display: none !important; }

        .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #D5C6A9; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #B69F78; }

        /* Hanging Tab Design without Full Width Background Box & Mentok Kiri */
        .draft-tabs-container {
            display: flex;
            align-items: flex-start;
            gap: 0px;
            padding: 0;
            background: transparent;
            border-top: none;
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            position: relative;
            scrollbar-width: thin;
            scrollbar-color: #D5C6A9 transparent;
        }
        .draft-tabs-container::-webkit-scrollbar {
            height: 4px;
        }
        .draft-tabs-container::-webkit-scrollbar-track {
            background: transparent;
        }
        .draft-tabs-container::-webkit-scrollbar-thumb {
            background: #D5C6A9;
            border-radius: 4px;
        }
        .draft-tab {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 500;
            color: #7A7A7A;
            background: var(--color-brand-10, rgb(247 245 244)); /* Warna brand-10 untuk tab tidak aktif */
            border: 1px solid #E8DED2;
            border-top: none;
            border-radius: 0 0 8px 8px;
            cursor: pointer;
            transition: all 0.15s ease;
            user-select: none;
            white-space: nowrap;
            position: relative;
            margin-left: -1px;
            flex-shrink: 0;
        }
        .draft-tab:first-child {
            margin-left: 0;
            border-left: 1px solid #E8DED2;
        }
        .draft-tab:hover {
            background: #EAE1D5;
            color: #4A4A4A;
        }
        .draft-tab.active {
            background: var(--color-brand-50, rgb(242 239 236)); /* Warna brand-50 menyatu dengan kertas editor */
            border-color: #E8DED2;
            border-top: none !important;
            color: #2C2C2C;
            font-weight: 600;
            box-shadow: none !important; /* Tanpa shadow agar terlihat menyatu langsung dengan text editor atasnya! */
            z-index: 20;
        }
        .draft-tab .draft-close {
            opacity: 0; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center;
            border-radius: 3px; color: #9A8E80; transition: all 0.1s;
        }
        .draft-tab:hover .draft-close, .draft-tab.active .draft-close { opacity: 1; }
        .draft-tab .draft-close:hover { background: #E8DED2; color: #E64C4C; }
        .draft-tab .draft-drag { opacity: 0; cursor: grab; color: #B0A090; transition: opacity 0.1s; }
        .draft-tab:hover .draft-drag { opacity: 1; }
        .draft-tab .draft-drag:active { cursor: grabbing; }
        .draft-drag-over { border-left: 2px solid #8C7558 !important; }

        .info-panel-scrollbar::-webkit-scrollbar { width: 4px; }
        .info-panel-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .info-panel-scrollbar::-webkit-scrollbar-thumb { background-color: #D5C6A9; border-radius: 10px; }
    </style>

    <div class="p-4 lg:p-6 max-w-7xl mx-auto">

        {{-- Breadcrumb (Standalone container with fixed margin so layout never jumps or drops down) --}}
        <div class="mb-8">
            <x-breadcrumb :items="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => '...', 'url' => route('projects.show', $project)],
                ['label' => 'Structure', 'url' => route('projects.structure', $project)],
                ['label' => 'Chapter ' . $chapterCard->order_index]
            ]" />
        </div>

        {{-- Absolute positioned Show Details Button container (takes ZERO vertical space in document flow so editor never drops down or moves!) --}}
        <div class="relative w-full z-20">
            <div x-show="!showDetailPanel"
                 x-cloak
                 x-transition:enter="transition ease-out duration-300 delay-150"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="absolute -top-8 left-0"
            >
                <button
                    type="button"
                    @click="showDetailPanel = true"
                    class="flex items-center gap-1.5 px-3 py-1 rounded-md border border-[#D5C6A9] bg-brand-50 hover:bg-[#EAE1D5] text-[#2C2C2C] text-[11.5px] font-semibold transition-all shadow-sm cursor-pointer"
                    title="Show Chapter Details Panel"
                >
                    <svg class="w-3.5 h-3.5 text-[#8C7558]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                    </svg>
                    <span>Show Details</span>
                </button>
            </div>
        </div>

        {{-- Main Layout: Details Card (Left, flex-1) + Editor Area (Right, flex-2) joined with border separator --}}
        <div class="flex relative overflow-hidden" style="height: calc(100vh - 145px); max-height: 90vh;">

            {{-- LEFT: Details Card (Curtain wrapper: clips fixed 280px card horizontally to eliminate text reflow bounce) --}}
            <div class="overflow-hidden transition-all duration-300 ease-in-out shrink-0 flex flex-col"
                 :class="showDetailPanel ? 'w-[280px] opacity-100' : 'w-0 opacity-0 pointer-events-none'"
            >
                <div class="w-[280px] h-full flex flex-col gap-5 overflow-y-auto info-panel-scrollbar p-5 bg-brand-50 border border-r-0 border-[#E8DED2] rounded-l-xl shadow-sm z-10 shrink-0">
                {{-- Top Row: Status Badge (Left) + Hide Details Button (Right) --}}
                {{-- Top Row: Status Dropdown (Left) + Hide Details Button (Right) --}}
                <div class="flex items-center justify-between relative" x-data="{ showStatusMenu: false }">
                    <div class="relative">
                        <button type="button" @click="showStatusMenu = !showStatusMenu" @class([
                            'w-full text-[12px] font-semibold px-3 py-1.5 rounded-lg flex items-center justify-between gap-1.5 shadow-sm border border-black/5 transition-all cursor-pointer hover:opacity-90',
                            'bg-warning-100/50 text-text-80' => $chapterCard->status === 'In Progress',
                            'bg-success-100/50 text-text-80' => $chapterCard->status === 'Completed',
                            'bg-text-100 text-text-80' => !in_array($chapterCard->status, ['In Progress', 'Completed'])
                        ])>
                            <div class="flex items-center gap-1.5 min-w-0">
                                <x-icons.chapter-status :status="$chapterCard->status" class="w-3.5 h-3.5 shrink-0" />
                                <span class="truncate">{{ $chapterCard->status ?? 'In Progress' }}</span>
                            </div>
                            <svg class="w-2.5 h-2.5 text-[#7A7A7A] shrink-0 ml-1 transition-transform" :class="showStatusMenu ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="showStatusMenu" @click.away="showStatusMenu = false" x-cloak
                             class="absolute left-0 top-full mt-1.5 w-full min-w-full bg-white rounded-lg shadow-lg border border-[#D5C6A9] py-1 z-50">
                            @foreach(['In Progress', 'Completed'] as $st)
                                @php
                                    $isSelected = $chapterCard->status === $st;
                                @endphp
                                <button
                                    type="button"
                                    @if(!$isSelected) wire:click="updateStatus('{{ $st }}')" @click="showStatusMenu = false" @endif
                                    @disabled($isSelected)
                                    @class([
                                        'w-full text-left px-3 py-2 text-[12px] font-medium flex items-center gap-2 transition-colors',
                                        'opacity-50 cursor-not-allowed bg-[#F5EFE6] text-[#6A6A6A]' => $isSelected,
                                        'hover:bg-[#EAE1D5] text-[#2C2C2C] cursor-pointer' => !$isSelected,
                                    ])
                                >
                                    <x-icons.chapter-status :status="$st" class="w-3.5 h-3.5 shrink-0" />
                                    <span class="truncate">{{ $st }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="showDetailPanel = false"
                        class="flex items-center gap-1 px-2 py-1 rounded-md border border-[#D5C6A9] bg-white/80 hover:bg-white text-[#4A4A4A] text-[11px] font-semibold transition-all shadow-sm"
                        title="Hide Chapter Details"
                    >
                        <svg class="w-3.5 h-3.5 text-[#8C7558]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                        </svg>
                        <span>Hide Details</span>
                    </button>
                </div>

                {{-- 1. Cover Image (Non-collapsible, always visible at top) --}}
                <div class="pb-1">
                    <div class="relative w-full aspect-[16/8] rounded-xl overflow-hidden bg-[#EAE1D5] border border-[#E0D5C5] shadow-inner group shrink-0 flex items-center justify-center">
                        @if($chapterCard->cover_image_path)
                            <img src="{{ Storage::url($chapterCard->cover_image_path) }}" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" alt="Chapter Cover">
                        @elseif($project->cover_image_path)
                            <img src="{{ Storage::url($project->cover_image_path) }}" class="w-full h-full object-cover opacity-80 transition-transform duration-300 group-hover:scale-105" alt="Project Cover">
                        @else
                            <div class="flex flex-col items-center justify-center gap-1 p-4 text-center">
                                <x-icons.no-structure class="w-8 h-8 text-[#B0A090] opacity-60" />
                                <span class="text-[11px] text-[#9A8E80] font-medium">No cover image</span>
                            </div>
                        @endif

                        {{-- Hover Action Buttons (Bottom Left, exactly like Project Cover) --}}
                        <div class="absolute bottom-2.5 left-2.5 z-30 flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                            <label class="flex items-center gap-1.5 px-2.5 py-1.5 bg-text-80/95 border border-text-60 hover:bg-text-80 text-bg-main text-[11px] font-semibold rounded-md shadow-lg cursor-pointer transition-transform active:scale-95">
                                <x-icons.upload class="w-3.5 h-3.5 text-bg-main" />
                                <span>{{ $chapterCard->cover_image_path || $project->cover_image_path ? 'Change Cover' : 'Upload Cover' }}</span>
                                <input type="file" wire:model="coverUpload" accept="image/*" class="hidden">
                            </label>

                            @if($chapterCard->cover_image_path || $project->cover_image_path)
                                <button type="button" wire:click="detachCoverImage" class="flex items-center gap-1.5 px-2.5 py-1.5 bg-text-80/95 border border-text-60 hover:bg-text-80 text-danger-100 text-[11px] font-semibold rounded-md shadow-lg cursor-pointer transition-transform active:scale-95" title="Detach Cover">
                                    <x-icons.delete class="w-3.5 h-3.5 text-danger-100" />
                                    <span>Remove</span>
                                </button>
                            @endif
                        </div>

                        <div wire:loading.flex wire:target="coverUpload" class="absolute inset-0 bg-black/60 backdrop-blur-[1px] items-center justify-center gap-2 text-white text-xs font-semibold z-40">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>Uploading Cover...</span>
                        </div>
                    </div>
                </div>

                {{-- 2. Chapter Info & Title Rename --}}
                <div x-data="{
                    editingTitle: false,
                    titleVal: '{{ addslashes($chapterCard->title) }}',
                    startEdit() {
                        this.editingTitle = true;
                        this.titleVal = '{{ addslashes($chapterCard->title) }}';
                        this.$nextTick(() => { $refs.titleInput.focus(); $refs.titleInput.select(); });
                    },
                    saveTitle() {
                        if (this.titleVal.trim() !== '') {
                            $wire.renameChapter(this.titleVal);
                        }
                        this.editingTitle = false;
                    }
                }" class="border-b border-[#E8DED2] pb-3.5">
                    <p class="text-[12.5px] font-medium text-[#8C7558] mb-1">Chapter {{ $chapterCard->order_index }}</p>

                    {{-- Display Title --}}
                    <div x-show="!editingTitle" class="group flex items-start justify-between gap-2 mb-1.5">
                        <h2 @dblclick="startEdit"
                            class="text-[22px] font-merriweather font-medium text-[#2C2C2C] leading-snug line-clamp-2 cursor-pointer hover:text-[#8C7558] transition-colors"
                            title="Double-click to rename chapter"
                        >
                            {{ $chapterCard->title }}
                        </h2>
                        <button type="button" @click="startEdit"
                                class="p-1 rounded hover:bg-[#EAE1D5] text-[#8C7558] opacity-70 group-hover:opacity-100 transition-opacity shrink-0 cursor-pointer"
                                title="Rename Chapter">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </button>
                    </div>

                    {{-- Inline Input Title --}}
                    <div x-show="editingTitle" x-cloak class="mb-1.5">
                        <input type="text" x-ref="titleInput" x-model="titleVal"
                               @keydown.enter="saveTitle" @keydown.escape="editingTitle = false" @blur="saveTitle"
                               class="w-full text-[20px] font-merriweather font-medium text-[#2C2C2C] bg-white border border-[#8C7558] rounded px-2 py-1 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#8C7558]/30">
                        <p class="text-[10px] text-[#8C7558] mt-0.5">Press Enter to save, Esc to cancel</p>
                    </div>

                    <p class="text-[11px] text-[#9A8E80]" wire:poll.3s>
                        Last Edited: {{ $chapterCard->updated_at?->timezone('Asia/Jakarta')->format('d F Y, H.i') ?? '-' }}
                    </p>
                </div>

                {{-- 3. Collapsible Section: Summary --}}
                <div x-data="{
                    openSummary: true,
                    editingSummary: false,
                    summaryVal: '',
                    startEditSummary() {
                        this.editingSummary = true;
                        this.summaryVal = $refs.summaryDisplay.dataset.raw || '';
                        this.$nextTick(() => { $refs.summaryInput.focus(); });
                    },
                    saveSummary() {
                        $wire.updateSummary(this.summaryVal);
                        this.editingSummary = false;
                    }
                }" class="border-b border-[#E8DED2] pb-3.5">
                    <div class="flex items-center justify-between py-1">
                        <button type="button" @click="openSummary = !openSummary" class="flex items-center gap-1.5 text-left group cursor-pointer flex-1">
                            <svg class="w-3.5 h-3.5 text-[#8C7558]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span class="text-[13px] font-bold text-[#2C2C2C] group-hover:text-[#8C7558] transition-colors">Summary</span>
                        </button>
                        <div class="flex items-center gap-2.5">
                            <button type="button" @click="if(!openSummary) openSummary = true; startEditSummary()" class="text-[11px] text-[#8C7558] hover:underline font-semibold cursor-pointer">Edit</button>
                            <button type="button" @click="openSummary = !openSummary" class="text-[#8C7558] transition-transform duration-200 cursor-pointer p-0.5" :class="openSummary ? 'rotate-180' : ''">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </div>
                    </div>

                    <div x-show="openSummary" x-cloak x-transition class="pt-2">
                        {{-- Display Summary (Full text when accordion is open, no Show More button) --}}
                        <div x-show="!editingSummary">
                            <div x-ref="summaryDisplay" data-raw="{{ $displaySummary === 'No summary available for this chapter yet.' ? '' : $displaySummary }}"
                                 @dblclick="startEditSummary"
                                 style="word-break: break-word; overflow-wrap: break-word;"
                                 class="p-2.5 rounded-lg border border-transparent hover:border-[#D5C6A9] hover:bg-[#EAE1D5]/40 transition-all cursor-pointer text-[13px] text-[#4A4A4A] leading-[1.65] break-words whitespace-normal overflow-x-hidden"
                                 title="Double click to edit summary">
                                {{ $displaySummary }}
                            </div>
                        </div>

                        {{-- Edit Summary Textarea --}}
                        <div x-show="editingSummary" x-cloak class="flex flex-col gap-1.5 mt-1">
                            <textarea x-ref="summaryInput" x-model="summaryVal" rows="4"
                                      style="word-break: break-word; overflow-wrap: break-word;"
                                      class="w-full text-[13px] text-[#2C2C2C] bg-white border border-[#8C7558] rounded-lg p-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#8C7558]/30 info-panel-scrollbar break-words whitespace-normal overflow-x-hidden"
                                      placeholder="Write chapter summary here..."></textarea>
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" @click="editingSummary = false" class="px-2 py-1 text-[11px] text-[#7A7A7A] hover:bg-[#EAE1D5] rounded font-medium cursor-pointer">Cancel</button>
                                <button type="button" @click="saveSummary" class="px-2.5 py-1 text-[11px] bg-[#8C7558] hover:bg-[#7A6548] text-white rounded font-semibold shadow-sm cursor-pointer">Save</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. Collapsible Section: Tags --}}
                <div x-data="{ openTags: true, addingTag: false, tagVal: '' }" class="border-b border-[#E8DED2] pb-3.5">
                    <div class="flex items-center justify-between py-1">
                        <button type="button" @click="openTags = !openTags" class="flex items-center gap-1.5 text-left group cursor-pointer flex-1">
                            <svg class="w-3.5 h-3.5 text-[#8C7558]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                            <span class="text-[13px] font-bold text-[#2C2C2C] group-hover:text-[#8C7558] transition-colors">Tags ({{ $chapterCard->tags->count() }})</span>
                        </button>
                        <button type="button" @click="openTags = !openTags" class="text-[#8C7558] transition-transform duration-200 cursor-pointer p-0.5" :class="openTags ? 'rotate-180' : ''">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </div>

                    <div x-show="openTags" x-cloak x-transition class="pt-2">
                        <div class="flex flex-wrap items-center gap-1.5 p-0.5">
                            {{-- Add Tag Button (inline when not adding) --}}
                            <button x-show="!addingTag" @click="addingTag = true; $nextTick(() => { $refs.tagInput.focus(); })"
                                    type="button"
                                    class="px-2.5 py-1 rounded-md border border-dashed border-[#D5C6A9] bg-white/40 text-[11.5px] text-[#8C7558] font-medium hover:bg-white transition-colors cursor-pointer">
                                + Add Tag
                            </button>

                            {{-- Full Width Compact Add Tag Input Box (when adding, takes full line so tags drop below) --}}
                            <div x-show="addingTag" x-cloak class="w-full p-1.5 rounded-lg bg-[#EAE1D5] border border-[#D5C6A9] shadow-2xs flex flex-col gap-0.5 mb-1.5">
                                <div class="flex items-center justify-between gap-1">
                                    <input type="text" x-ref="tagInput" x-model="tagVal"
                                           @input="if(tagVal.length > 20) tagVal = tagVal.substring(0, 20)"
                                           @keydown.enter="if(tagVal.trim() !== '') { $wire.addTag(tagVal); tagVal = ''; addingTag = false; }"
                                           @keydown.escape="addingTag = false; tagVal = ''"
                                           placeholder="Tag..."
                                           maxlength="20"
                                           class="w-full bg-transparent border-0 border-b border-[#8C7558] px-0.5 py-0.5 text-[11.5px] text-[#2C2C2C] placeholder-[#8C7558]/60 focus:outline-none focus:ring-0 focus:border-[#5C4A38] font-medium leading-tight">
                                    <button type="button" @click="addingTag = false; tagVal = ''" class="text-[#8C7558] hover:text-[#2C2C2C] font-bold text-xs px-1 leading-none transition-colors cursor-pointer" title="Cancel">&times;</button>
                                </div>
                                <div class="flex justify-end">
                                    <span class="text-[9px] font-semibold text-[#8C7558] leading-none" x-text="tagVal.length + '/20'">0/20</span>
                                </div>
                            </div>

                            @if($chapterCard->tags && $chapterCard->tags->isNotEmpty())
                                @foreach($chapterCard->tags as $tag)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-[#EAE1D5] text-[11.5px] text-[#4A4A4A] font-medium border border-[#D5C6A9] max-w-[140px] shadow-2xs group"
                                          title="{{ $tag->name }}">
                                        <span class="truncate">{{ $tag->name }}</span>
                                        <button type="button" wire:click="removeTag({{ $tag->id }})" class="text-[#8C7558] hover:text-red-600 opacity-60 group-hover:opacity-100 transition-opacity ml-0.5 cursor-pointer" title="Remove tag">&times;</button>
                                    </span>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                {{-- 5. Collapsible Section: Characters --}}
                <div x-data="{ openChar: true, showCharDropdown: false }" class="pb-2">
                    <div class="flex items-center justify-between py-1">
                        <button type="button" @click="openChar = !openChar" class="flex items-center gap-1.5 text-left group cursor-pointer flex-1">
                            <svg class="w-3.5 h-3.5 text-[#8C7558]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span class="text-[13px] font-bold text-[#2C2C2C] group-hover:text-[#8C7558] transition-colors">Characters ({{ $chapterCard->characters->count() }})</span>
                        </button>
                        <button type="button" @click="openChar = !openChar" class="text-[#8C7558] transition-transform duration-200 cursor-pointer p-0.5" :class="openChar ? 'rotate-180' : ''">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </div>

                    <div x-show="openChar" x-cloak x-transition class="pt-2">
                        {{-- Add Character Button + Dropdown Menu (At the very top) --}}
                        <div class="relative">
                            <button type="button" @click="showCharDropdown = !showCharDropdown"
                                    class="w-full flex items-center justify-center gap-2 px-3.5 py-2 rounded-lg border border-dashed border-[#D5C6A9] bg-white/40 text-[12px] text-[#8C7558] font-medium hover:bg-white transition-colors shadow-sm cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>Add Character...</span>
                            </button>

                            <div x-show="showCharDropdown" @click.away="showCharDropdown = false" x-cloak
                                 class="absolute left-0 top-full mt-1.5 w-full bg-white rounded-lg shadow-xl border border-[#D5C6A9] max-h-[200px] overflow-y-auto info-panel-scrollbar z-50 py-1">
                                @if(empty($projectCharacters) || $projectCharacters->isEmpty())
                                    <div class="px-3 py-3 text-center text-[11.5px] text-[#9A8E80]">
                                        No characters found in this project yet.
                                    </div>
                                @else
                                    @php
                                        $addedCharIds = $chapterCard->characters->pluck('character_id')->toArray();
                                    @endphp
                                    @foreach($projectCharacters as $pChar)
                                        @php
                                            $isCharAdded = in_array($pChar->character_id, $addedCharIds);
                                        @endphp
                                        <button
                                            type="button"
                                            @if(!$isCharAdded) wire:click="attachCharacter('{{ $pChar->character_id }}')" @click="showCharDropdown = false" @endif
                                            @class([
                                                'w-full flex items-center gap-2.5 px-3 py-2 text-left text-[12px] font-medium transition-colors border-b border-[#EAE1D5]/40 last:border-0',
                                                'opacity-50 cursor-not-allowed bg-[#F5EFE6]' => $isCharAdded,
                                                'hover:bg-[#EAE1D5] text-[#2C2C2C] cursor-pointer' => !$isCharAdded,
                                            ])
                                        >
                                            @if($pChar->image_path)
                                                <img src="{{ Storage::url($pChar->image_path) }}" class="w-6 h-6 rounded-full object-cover shrink-0 border border-[#D5C6A9]" alt="">
                                            @else
                                                <div class="w-6 h-6 rounded-full bg-[#EAE1D5] overflow-hidden flex items-center justify-center shrink-0 border border-[#D5C6A9]">
                                                    <x-icons.default-avatar class="w-4 h-4 text-[#8C7558]" />
                                                </div>
                                            @endif
                                            <span class="truncate flex-1">{{ $pChar->full_name ?? $pChar->nick_name ?? 'Unnamed' }}</span>
                                            @if($isCharAdded)
                                                <span class="text-[10px] text-[#8C7558] font-semibold bg-[#EAE1D5] px-1.5 py-0.5 rounded">Added</span>
                                            @else
                                                <span class="text-[11px] text-[#8C7558] font-bold opacity-0 hover:opacity-100">+</span>
                                            @endif
                                        </button>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        {{-- Attached Characters List (No scrolling, show all directly below Add Character button) --}}
                        @if($chapterCard->characters && $chapterCard->characters->isNotEmpty())
                            <div class="flex flex-col gap-1.5 mt-2">
                                @foreach($chapterCard->characters as $char)
                                    <div class="flex items-center justify-between p-2 rounded-lg bg-[#EAE1D5]/60 border border-[#D5C6A9]/60 group">
                                        <div class="flex items-center gap-2 min-w-0">
                                            @if($char->image_path)
                                                <img src="{{ Storage::url($char->image_path) }}" class="w-7 h-7 rounded-full object-cover shrink-0 border border-[#D5C6A9]" alt="">
                                            @else
                                                <div class="w-7 h-7 rounded-full bg-[#EAE1D5] overflow-hidden flex items-center justify-center shrink-0 border border-[#D5C6A9]">
                                                    <x-icons.default-avatar class="w-5 h-5 text-[#8C7558]" />
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="text-[12px] font-semibold text-[#2C2C2C] truncate">{{ $char->full_name ?? $char->nick_name ?? 'Unnamed' }}</p>
                                                @if($char->nick_name && $char->nick_name !== $char->full_name)
                                                    <p class="text-[10px] text-[#8C7558] truncate">"{{ $char->nick_name }}"</p>
                                                @endif
                                            </div>
                                        </div>
                                        <button type="button" wire:click="detachCharacter('{{ $char->character_id }}')"
                                                class="text-[#8C7558] hover:text-red-600 opacity-50 group-hover:opacity-100 transition-opacity p-1 cursor-pointer" title="Remove character from chapter">
                                            &times;
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT: Editor Area + Tabs (flex-1 dynamically fills remaining space, much wider than details) --}}
        <div class="min-w-0 flex-1 flex flex-col relative">
                {{-- Editor Box (Joined border: border-l separates from details when open) --}}
                <div class="border border-b-0 border-[#E8DED2] shadow-sm overflow-hidden bg-brand-50 flex-1 flex flex-col relative min-h-0"
                     :class="showDetailPanel ? 'rounded-tr-xl border-l' : 'rounded-t-xl border-l'"
                >
                    @if($activeDraft)
                        <x-text-editor 
                            editorId="manuscript-editor"
                            updateMethod="updateContent"
                            contentProp="editorBody"
                            counterType="word"
                            :showStrike="false"
                            :showTodo="true"
                            wire:key="editor-{{ $activeDraft->manuscript_id }}"
                        />
                    @endif
                </div>

                {{-- Tabs Bar (No background box, flush left, hanging tabs) --}}
                <div class="draft-tabs-container shrink-0"
                     x-data="{
                         draggedDraftId: null,
                         dragOverDraftId: null
                     }"
                >
                    @foreach($drafts as $draft)
                        <div
                            class="draft-tab {{ $activeDraftId === $draft->manuscript_id ? 'active' : '' }}"
                            :class="{
                                'draft-drag-over': dragOverDraftId === '{{ $draft->manuscript_id }}'
                            }"
                            data-draft-id="{{ $draft->manuscript_id }}"
                            wire:click="selectDraft('{{ $draft->manuscript_id }}')"
                            draggable="true"
                            @dragstart="draggedDraftId = '{{ $draft->manuscript_id }}'; $event.dataTransfer.effectAllowed = 'move'; $event.dataTransfer.dropEffect = 'move';"
                            @dragover.prevent="if (draggedDraftId && draggedDraftId !== '{{ $draft->manuscript_id }}') dragOverDraftId = '{{ $draft->manuscript_id }}';"
                            @dragenter.prevent="if (draggedDraftId && draggedDraftId !== '{{ $draft->manuscript_id }}') dragOverDraftId = '{{ $draft->manuscript_id }}';"
                            @dragleave="if (dragOverDraftId === '{{ $draft->manuscript_id }}') dragOverDraftId = null;"
                            @drop.prevent="
                                if (draggedDraftId && draggedDraftId !== '{{ $draft->manuscript_id }}') {
                                    $wire.moveDraft(draggedDraftId, '{{ $draft->manuscript_id }}');
                                }
                                draggedDraftId = null;
                                dragOverDraftId = null;
                            "
                            @dragend="draggedDraftId = null; dragOverDraftId = null;"
                        >
                            {{-- Drag grip --}}
                            <span class="draft-drag" title="Drag to reorder">
                                <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor">
                                    <circle cx="8" cy="6" r="1.5"/><circle cx="16" cy="6" r="1.5"/>
                                    <circle cx="8" cy="12" r="1.5"/><circle cx="16" cy="12" r="1.5"/>
                                    <circle cx="8" cy="18" r="1.5"/><circle cx="16" cy="18" r="1.5"/>
                                </svg>
                            </span>

                            {{-- Title / Rename --}}
                            <span
                                x-show="renamingDraftId !== '{{ $draft->manuscript_id }}'"
                                @dblclick.stop="startRenameDraft('{{ $draft->manuscript_id }}', '{{ addslashes($draft->title ?? 'Draft ' . $loop->iteration) }}')"
                                class="truncate max-w-[120px]"
                                title="Double-click to rename"
                            >{{ $draft->title ?? 'Draft ' . $loop->iteration }}</span>

                            <input
                                x-show="renamingDraftId === '{{ $draft->manuscript_id }}'"
                                x-cloak
                                id="draft-rename-{{ $draft->manuscript_id }}"
                                x-model="renameValue"
                                @keydown.enter.stop="commitRenameDraft('{{ $draft->manuscript_id }}')"
                                @keydown.escape.stop="renamingDraftId = null"
                                @blur="commitRenameDraft('{{ $draft->manuscript_id }}')"
                                @click.stop
                                class="w-20 text-[12px] text-[#2C2C2C] bg-white border border-[#D5C6A9] rounded px-1 py-0 outline-none focus:border-[#8C7558]"
                            />

                            {{-- Close/Delete --}}
                            @if($drafts->count() > 1)
                                <button
                                    class="draft-close"
                                    @click.stop="confirmDeleteDraft('{{ $draft->manuscript_id }}')"
                                    title="Delete draft"
                                >
                                    <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    @endforeach

                    {{-- Add Draft Button --}}
                    <button
                        wire:click="addDraft"
                        class="shrink-0 w-7 h-7 flex items-center justify-center text-[#9A8E80] hover:text-[#5E4C38] hover:bg-[#EAE1D5] rounded transition-colors ml-1 mt-0.5"
                        title="Add new draft"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </button>

                    {{-- Completes the bottom border of the editor box to the right --}}
                    <div class="flex-1 min-w-[20px] border-t border-[#E8DED2] self-start mt-0 shrink-0"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Draft Confirm Dialog --}}
    <x-confirm-dialog
        eventName="open-delete-draft-dialog"
        title="Delete Draft"
        description="Are you sure you want to delete this draft? This action cannot be undone and all content in this draft will be permanently removed."
        confirmText="Yes, Delete"
        cancelText="Cancel"
        submitAction="deleteDraft"
    >
        <x-slot:icon>
            <x-icons.delete size="w-10 h-10" color="currentColor"/>
        </x-slot:icon>
    </x-confirm-dialog>
</div>