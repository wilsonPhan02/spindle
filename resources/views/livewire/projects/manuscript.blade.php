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
use App\Traits\HandlesFileUpload;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads, HandlesFileUpload;

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
        $allDrafts = $chapterCard->manuscript()->orderBy('created_at')->get();

        if ($allDrafts->isEmpty()) {
            $draft = Manuscript::create([
                'chapter_card_id' => $chapterCard->chapter_card_id,
                'title' => 'Draft 1',
                'content' => '',
                'word_count' => 0,
            ]);
            $this->activeDraftId = $draft->manuscript_id;
            $this->editorBody = '';
        } else {
            foreach ($allDrafts as $idx => $d) {
                if (empty(trim($d->title ?? ''))) {
                    $d->update(['title' => 'Draft ' . ($idx + 1)]);
                }
            }
            $draft = $allDrafts->first();
            $this->activeDraftId = $draft->manuscript_id;
            $this->editorBody = $draft->content ?? '';
        }
    }

    // ----------------
    // Draft Actions
    // ----------------

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

        $existingTitles = Manuscript::where('chapter_card_id', $this->chapterCard->chapter_card_id)
            ->pluck('title')
            ->map(fn($t) => strtolower(trim($t ?? '')))
            ->toArray();

        $nextNum = count($existingTitles) + 1;
        while (in_array(strtolower("draft {$nextNum}"), $existingTitles)) {
            $nextNum++;
        }

        $draft = Manuscript::create([
            'chapter_card_id' => $this->chapterCard->chapter_card_id,
            'title' => "Draft {$nextNum}",
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
        $this->refreshAutoSummary();
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
            $this->selectDraft($draggedId);
            $this->refreshAutoSummary();
            $this->chapterCard->touch();
            $this->chapterCard->refresh();
        }
    }

    // ------------------
    // Editor Actions
    // ------------------

    public function updateContent(string $html): void
    {
        $this->editorBody = $html;
        $this->saveCurrentContent();
    }

    public function saveCurrentContent(): void
    {
        if (!$this->activeDraftId) return;

        $wordCount = \App\Helpers\TextHelper::wordCount($this->editorBody);

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
            $this->refreshAutoSummary();
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

    // --------------------------------
    // Chapter Details Panel Actions
    // --------------------------------

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
            'coverUpload' => 'image|max:5120',
        ], [
            'coverUpload.max' => 'The selected image is too large. The maximum allowed file size is 5MB.',
            'coverUpload.image' => 'The selected file type is not supported. Please upload an image.',
        ]);

        if ($this->coverUpload) {
            $path = $this->replaceImage($this->coverUpload, $this->chapterCard->cover_image_path, 'chapter_covers');
            $this->chapterCard->update(['cover_image_path' => $path]);
            $this->coverUpload = null;
            $this->chapterCard->refresh();
        }
    }

    public function detachCoverImage(): void
    {
        if ($this->chapterCard->cover_image_path) {
            $this->deleteImage($this->chapterCard->cover_image_path);
            $this->chapterCard->update(['cover_image_path' => null]);
            $this->chapterCard->refresh();
        } elseif ($this->project->cover_image_path) {
            $this->deleteImage($this->project->cover_image_path);
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
        $trimmed = trim($newSummary);
        if ($trimmed === '') {
            $this->chapterCard->update([
                'summary' => null,
                'is_custom_summary' => false,
            ]);
            $this->refreshAutoSummary();
        } else {
            $this->chapterCard->update([
                'summary' => $trimmed,
                'is_custom_summary' => true,
            ]);
        }
        $this->chapterCard->refresh();
    }

    public function refreshAutoSummary(): void
    {
        if ($this->chapterCard->is_custom_summary) {
            return;
        }

        $firstDraft = Manuscript::where('chapter_card_id', $this->chapterCard->chapter_card_id)
            ->orderBy('created_at')
            ->first();

        $autoSummary = null;
        if ($firstDraft && !empty($firstDraft->content)) {
            $autoSummary = \App\Helpers\TextHelper::extractSentences($firstDraft->content);
        }

        $this->chapterCard->update(['summary' => $autoSummary]);
        $this->chapterCard->refresh();
    }

    public function with(): array
    {
        $this->chapterCard->refresh();
        if (!$this->chapterCard->is_custom_summary && empty(trim($this->chapterCard->summary ?? ''))) {
            $this->refreshAutoSummary();
        }

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

        $displaySummary = trim($this->chapterCard->summary ?? '');

        return [
            'drafts' => $drafts,
            'activeDraft' => $activeDraft,
            'projectCharacters' => $projectCharacters,
            'displaySummary' => $displaySummary !== '' ? $displaySummary : 'No summary available for this chapter yet.',
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
            const elInput = document.getElementById('draft-rename-' + id);
            const spanEl = elInput ? elInput.previousElementSibling : null;
            this.renameValue = (spanEl && spanEl.innerText.trim()) ? spanEl.innerText.trim() : currentTitle;
            this.$nextTick(() => {
                if (elInput) { elInput.focus(); elInput.select(); }
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

        /* Hanging Tab Design */
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
        }

        .draft-tab {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: var(--color-brand-10);
            border: 1px solid var(--color-brand-100);
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
            border-left: 1px solid var(--color-brand-100);
        }
        .draft-tab:hover {
            background: var(--color-brand-150);
        }
        .draft-tab.active {
            background: var(--color-brand-50);
            border-top: none !important;
            color: var(--color-text-80);
            font-weight: 600;
            box-shadow: none !important;
            z-index: 20;
        }
        .draft-tab .draft-close {
            opacity: 0; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center;
            border-radius: 3px; color: var(--color-subtext-90); transition: all 0.1s;
        }
        .draft-tab:hover .draft-close, .draft-tab.active .draft-close { opacity: 1; }
        .draft-tab .draft-close:hover { background: var(--color-brand-150); color: var(--color-danger-100); }
        .draft-tab .draft-drag { opacity: 0; cursor: grab; color: var(--color-secondary-200); transition: opacity 0.1s; }
        .draft-tab:hover .draft-drag { opacity: 1; }
        .draft-tab .draft-drag:active { cursor: grabbing; }
        .draft-drag-over { border-left: 2px solid var(--color-secondary-200) !important; }
    </style>

    <div class="p-4 lg:p-6 max-w-7xl mx-auto">

        {{-- Breadcrumb --}}
        <div class="mb-8">
            <x-breadcrumb :items="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => '...', 'url' => route('projects.show', $project)],
                ['label' => 'Structure', 'url' => route('projects.structure', $project)],
                ['label' => 'Chapter ' . $chapterCard->order_index]
            ]" />
        </div>

        {{-- Show Detail Button --}}
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
                    class="flex items-center gap-1.5 px-3 py-1 rounded-md border border-secondary-150 bg-card-bg hover:bg-card-hover text-text-80 text-app-caption transition-all shadow-sm cursor-pointer"
                    title="Show Chapter Details Panel"
                >
                    <x-icons.chevron size="w-3 h-3"/>
                    <span>Show Details</span>
                </button>
            </div>
        </div>

        {{-- Main Layout --}}
        <div class="flex relative overflow-hidden" style="height: calc(100vh - 145px); max-height: 90vh;">

            {{-- LEFT: Details Card --}}
            @include('livewire.projects.partials.chapter-details-panel')
    </div>

        {{-- RIGHT: Editor Area + Tabs --}}
        <div class="min-w-0 flex-1 flex flex-col relative">
                <div class="border border-b-0 border-brand-150 shadow-sm overflow-hidden bg-brand-50 flex-1 flex flex-col relative min-h-0"
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

                {{-- Tabs Bar --}}
                @include('livewire.projects.partials.draft-tabs')
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
            <x-icons.delete class="w-15 h-15"/>
        </x-slot:icon>
    </x-confirm-dialog>
</div>