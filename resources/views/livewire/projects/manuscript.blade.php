<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use App\Models\Project;
use App\Models\ChapterCard;
use App\Models\Manuscript;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public Project $project;
    public ChapterCard $chapterCard;
    public ?string $activeDraftId = null;
    public string $editorBody = '';
    public $uploadFile;

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

    public function with(): array
    {
        $drafts = Manuscript::where('chapter_card_id', $this->chapterCard->chapter_card_id)
            ->orderBy('created_at')
            ->get();

        $activeDraft = $this->activeDraftId
            ? $drafts->firstWhere('manuscript_id', $this->activeDraftId)
            : null;

        $this->chapterCard->load('tags');

        return [
            'drafts' => $drafts,
            'activeDraft' => $activeDraft,
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
                 x-transition:enter="transition ease-out duration-150 delay-75"
                 x-transition:enter-start="opacity-0 -translate-x-2"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="absolute -top-8 left-0"
            >
                <button
                    type="button"
                    @click="showDetailPanel = true"
                    class="flex items-center gap-1.5 px-3 py-1 rounded-md border border-[#D5C6A9] bg-brand-50 hover:bg-[#EAE1D5] text-[#2C2C2C] text-[11.5px] font-semibold transition-all shadow-sm"
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
        <div class="flex relative" style="height: calc(100vh - 145px); max-height: 90vh;">

            {{-- LEFT: Details Card (flex-1, symmetrical p-6 padding, attached to editor with right border separator) --}}
            <div x-show="showDetailPanel"
                 x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-x-3"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition-opacity ease-in duration-75"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="w-1/3 flex-[1] min-w-[300px] max-w-[360px] shrink-0 flex flex-col gap-5 overflow-y-auto info-panel-scrollbar p-6 bg-brand-50 border border-r-0 border-[#E8DED2] rounded-l-xl shadow-sm z-10"
            >
                {{-- Top Row: Status Badge (Left) + Hide Details Button (Right) --}}
                <div class="flex items-center justify-between">
                    <span @class([
                        'text-[12px] font-semibold px-3 py-1.5 rounded-lg flex items-center gap-1.5 shadow-sm border border-black/5',
                        'bg-[#FEF3C7] text-text-80' => $chapterCard->status === 'In Progress',
                        'bg-[#D1FAE5] text-text-80' => $chapterCard->status === 'Completed',
                        'bg-[#EAE1D5] text-text-80' => !in_array($chapterCard->status, ['In Progress', 'Completed'])
                    ])>
                        <x-icons.chapter-status :status="$chapterCard->status" class="w-3.5 h-3.5 shrink-0" />
                        <span>{{ $chapterCard->status ?? 'In Progress' }}</span>
                        <svg class="w-2.5 h-2.5 text-[#7A7A7A] shrink-0 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </span>

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

                {{-- Cover Image --}}
                <div class="w-full aspect-[16/8] rounded-xl overflow-hidden bg-[#EAE1D5] border border-[#E0D5C5] shadow-inner">
                    @if($project->cover_image_path)
                        <img src="{{ Storage::url($project->cover_image_path) }}" class="w-full h-full object-cover" alt="Cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <x-icons.no-structure class="w-12 h-12 text-[#B0A090] opacity-60" />
                        </div>
                    @endif
                </div>

                {{-- Chapter Info --}}
                <div>
                    <p class="text-[12.5px] font-medium text-[#8C7558] mb-1">Chapter {{ $chapterCard->order_index }}</p>
                    <h2 class="text-[24px] font-merriweather font-medium text-[#2C2C2C] leading-snug mb-1.5 line-clamp-2">
                        {{ $chapterCard->title }}
                    </h2>
                    <p class="text-[11px] text-[#9A8E80]">
                        Last Edited: {{ $chapterCard->updated_at?->format('d F Y, H.i') ?? '-' }}
                    </p>
                </div>

                {{-- Synopsis --}}
                <div>
                    <p class="text-[13px] text-[#4A4A4A] leading-[1.65]" style="display:-webkit-box; -webkit-line-clamp:6; -webkit-box-orient:vertical; overflow:hidden;">
                        {{ $chapterCard->summary ?? 'No summary available for this chapter yet.' }}
                    </p>
                </div>

                {{-- Tags --}}
                <div>
                    <div class="flex items-center gap-1.5 mb-2.5">
                        <svg class="w-3.5 h-3.5 text-[#8C7558]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                        <span class="text-[12.5px] font-semibold text-[#2C2C2C]">Tags</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        @if($chapterCard->tags && $chapterCard->tags->isNotEmpty())
                            @foreach($chapterCard->tags as $tag)
                                <span class="px-3 py-1 rounded-md bg-[#EAE1D5] text-[11.5px] text-[#4A4A4A] font-medium border border-[#D5C6A9]">
                                    {{ $tag->name }}
                                </span>
                            @endforeach
                        @endif
                        <button class="px-3 py-1 rounded-md border border-dashed border-[#D5C6A9] bg-white/40 text-[11.5px] text-[#8C7558] font-medium hover:bg-white transition-colors">
                            + Add Tag
                        </button>
                    </div>
                </div>

                {{-- Characters --}}
                <div class="pb-2">
                    <h3 class="text-[13.5px] font-semibold text-[#2C2C2C] mb-2.5">Characters</h3>
                    <button class="w-full flex items-center gap-2 px-3.5 py-2.5 rounded-lg border border-dashed border-[#D5C6A9] bg-white/40 text-[12px] text-[#8C7558] font-medium hover:bg-white transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Add Character...</span>
                    </button>
                </div>
            </div>

            {{-- RIGHT: Editor Area + Tabs (flex-2 when details open, flex-1 when closed) --}}
            <div class="min-w-0 flex flex-col relative transition-all duration-200 ease-out"
                 :class="showDetailPanel ? 'flex-[2] w-2/3' : 'w-full flex-1'"
            >
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
                            :showTodo="false"
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
            <x-icons.delete-default size="w-10 h-10" color="currentColor"/>
        </x-slot:icon>
    </x-confirm-dialog>
</div>