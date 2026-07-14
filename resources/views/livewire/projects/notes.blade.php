<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use App\Models\Note;
use App\Models\Project;
use App\Helpers\TextHelper;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public Project $project;
    public ?string $activeNoteId = null;
    public string $editorBody = '';
    public ?int $lastCursorOffset = null;
    public $uploadFile;
    public array $undoStack = [];
    public array $redoStack = [];

    public function mount(Project $project): void
    {
        $this->project = $project;

        // Jika ada note yang diminta lewat query string (?note=xxx), misalnya dari
        // klik salah satu item di card "Notes" pada halaman project, buka note itu
        // secara langsung. Jika tidak ada / tidak valid, fallback ke note pertama.
        $requestedNoteId = request()->query('note');
        $selected = null;

        if ($requestedNoteId) {
            $selected = Note::where('note_id', $requestedNoteId)
                            ->where('project_id', $project->project_id)
                            ->first();
        }

        if (!$selected) {
            $selected = Note::where('project_id', $project->project_id)
                         ->whereNull('parent_note_id')
                         ->orderBy('sort_order')
                         ->first();
        }

        if ($selected) {
            $this->activeNoteId = $selected->note_id;
            $this->editorBody   = $selected->body ?? '';
        }
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function nextDefaultTitle(?string $parentId = null): string
    {
        return TextHelper::uniqueName(
            'Untitled Notes',
            fn () => Note::where('project_id', $this->project->project_id)
                         ->where('parent_note_id', $parentId)
                         ->pluck('title')
        );
    }

    private function nextSortOrder(?string $parentId = null): int
    {
        return (int) Note::where('project_id', $this->project->project_id)
                         ->where('parent_note_id', $parentId)
                         ->max('sort_order') + 1;
    }

    // -----------------------------------------------------------------------
    // Actions & Undo System
    // -----------------------------------------------------------------------

    public function pushUndo(string $action, array $data, string $message): void
    {
        $this->undoStack[] = [
            'action'    => $action,
            'data'      => $data,
            'timestamp' => time(),
        ];
        $this->redoStack = [];
        
        $this->dispatch('show-undo-toast', message: $message);
    }

    public function undo(): void
    {
        if (empty($this->undoStack)) return;

        $lastAction = array_pop($this->undoStack);
        $action = $lastAction['action'];
        $data   = $lastAction['data'];

        if ($action === 'add') {
            $note = Note::find($data['note_id']);
            if ($note) $note->delete();
            
            if ($this->activeNoteId === $data['note_id']) {
                $this->activeNoteId = null;
                $this->editorBody = '';
                $next = Note::where('project_id', $this->project->project_id)
                            ->whereNull('parent_note_id')->orderBy('sort_order')->first();
                if ($next) {
                    $this->selectNote($next->note_id, false);
                } else {
                    $this->dispatch('refresh-editor-content');
                }
            }
        } elseif ($action === 'remove') {
            $note = Note::withTrashed()->find($data['note_id']);
            if ($note) {
                $note->restore();
                $this->selectNote($note->note_id, false);
            }
        } elseif ($action === 'rename') {
            $note = Note::find($data['note_id']);
            if ($note) {
                $note->update(['title' => $data['old_title']]);
                $this->selectNote($data['note_id'], false);
            }
        } elseif ($action === 'move') {
            foreach ($data['old_positions'] as $id => $pos) {
                Note::where('note_id', $id)->update([
                    'parent_note_id' => $pos['parent_note_id'],
                    'depth'          => $pos['depth'],
                    'sort_order'     => $pos['sort_order'],
                ]);
            }
            if (!empty($data['moved_id'])) {
                $this->selectNote($data['moved_id'], false);
            }
        } elseif ($action === 'edit') {
            $note = Note::find($data['note_id']);
            if ($note) {
                if ($this->activeNoteId !== $data['note_id']) {
                    $this->saveCurrentBody();
                    $this->activeNoteId = $data['note_id'];
                }
                $note->update(['body' => $data['old_body']]);
                $this->editorBody = $data['old_body'];
                $this->lastCursorOffset = $data['old_cursor'] ?? null;
                $this->dispatch('refresh-editor-content', cursor: $data['old_cursor'] ?? $data['new_cursor'] ?? null);
            }
        }
        
        $this->redoStack[] = $lastAction;
        $this->dispatch('show-undo-toast', message: __('Action undone.'));
    }

    public function redo(): void
    {
        if (empty($this->redoStack)) return;

        $lastAction = array_pop($this->redoStack);
        $action = $lastAction['action'];
        $data   = $lastAction['data'];

        if ($action === 'add') {
            $note = Note::withTrashed()->find($data['note_id']);
            if ($note) {
                $note->restore();
                $this->selectNote($note->note_id, false);
            }
        } elseif ($action === 'remove') {
            $note = Note::find($data['note_id']);
            if ($note) {
                $note->delete();
                if ($this->activeNoteId === $data['note_id']) {
                    $this->activeNoteId = null;
                    $this->editorBody = '';
                    $next = Note::where('project_id', $this->project->project_id)
                                ->whereNull('parent_note_id')->orderBy('sort_order')->first();
                    if ($next) {
                        $this->selectNote($next->note_id, false);
                    } else {
                        $this->dispatch('refresh-editor-content');
                    }
                }
            }
        } elseif ($action === 'rename') {
            $note = Note::find($data['note_id']);
            if ($note) {
                $note->update(['title' => $data['new_title']]);
                $this->selectNote($data['note_id'], false);
            }
        } elseif ($action === 'move') {
            foreach ($data['new_positions'] as $id => $pos) {
                Note::where('note_id', $id)->update([
                    'parent_note_id' => $pos['parent_note_id'],
                    'depth'          => $pos['depth'],
                    'sort_order'     => $pos['sort_order'],
                ]);
            }
            if (!empty($data['moved_id'])) {
                $this->selectNote($data['moved_id'], false);
            }
        } elseif ($action === 'edit') {
            $note = Note::find($data['note_id']);
            if ($note) {
                if ($this->activeNoteId !== $data['note_id']) {
                    $this->saveCurrentBody();
                    $this->activeNoteId = $data['note_id'];
                }
                $note->update(['body' => $data['new_body']]);
                $this->editorBody = $data['new_body'];
                $this->lastCursorOffset = $data['new_cursor'] ?? null;
                $this->dispatch('refresh-editor-content', cursor: $data['new_cursor'] ?? null);
            }
        }
        
        $this->undoStack[] = $lastAction;
        $this->dispatch('show-undo-toast', message: __('Action redone.'));
    }

    public function undoWithCurrentBody(?string $currentHtml = null, ?int $cursorOffset = null): void
    {
        if ($currentHtml !== null && $this->activeNoteId && $this->editorBody !== $currentHtml) {
            $this->updateBody($currentHtml, $cursorOffset);
        }
        $this->undo();
    }

    public function redoWithCurrentBody(?string $currentHtml = null, ?int $cursorOffset = null): void
    {
        if ($currentHtml !== null && $this->activeNoteId && $this->editorBody !== $currentHtml) {
            $this->updateBody($currentHtml, $cursorOffset);
        }
        $this->redo();
    }

    public function addNote(): void
    {
        $note = Note::create([
            'project_id'     => $this->project->project_id,
            'parent_note_id' => null,
            'depth'          => 0,
            'sort_order'     => $this->nextSortOrder(null),
            'title'          => $this->nextDefaultTitle(null),
            'body'           => '',
        ]);
        $this->pushUndo('add', ['note_id' => $note->note_id], __('Note created.'));
        $this->selectNote($note->note_id, false);
    }

    public function addSubTab(string $parentId): void
    {
        $parent = Note::find($parentId);
        if (!$parent || !$parent->canHaveChildren()) return;

        $note = Note::create([
            'project_id'     => $this->project->project_id,
            'parent_note_id' => $parentId,
            'depth'          => $parent->depth + 1,
            'sort_order'     => $this->nextSortOrder($parentId),
            'title'          => $this->nextDefaultTitle($parentId),
            'body'           => '',
        ]);
        $this->pushUndo('add', ['note_id' => $note->note_id], __('Sub tab created.'));
        $this->selectNote($note->note_id, false);
    }

    public function selectNote(string $noteId, bool $recordUndo = false): void
    {
        $this->saveCurrentBody();
        $note = Note::find($noteId);
        if (!$note) return;

        $this->activeNoteId = $noteId;
        $this->editorBody   = $note->body ?? '';
        $this->lastCursorOffset = null;
        $this->dispatch('refresh-editor-content');
    }

    public function renameNote(string $noteId, string $newTitle): void
    {
        $note = Note::where('note_id', $noteId)
                    ->where('project_id', $this->project->project_id)
                    ->first();
        if (!$note) return;

        $trimmed = trim($newTitle);
        $hasCopy = str_ends_with($trimmed, ' (Copy)');
        $base = $hasCopy ? substr($trimmed, 0, -7) : $trimmed;
        $finalTitle = mb_substr($base, 0, 25) . ($hasCopy ? ' (Copy)' : '');

        if ($finalTitle === '' || $finalTitle === ' (Copy)') return;
        
        $this->pushUndo('rename', ['note_id' => $noteId, 'old_title' => $note->title, 'new_title' => $finalTitle], __('Note renamed.'));
        
        $note->update(['title' => $finalTitle]);
    }

    public function duplicateNote(string $noteId): void
    {
        $source = Note::with('childrenRecursive')->find($noteId);
        if (!$source) return;
        $duplicate = $this->cloneNoteRecursive($source, $source->parent_note_id, $source->depth);
        $this->pushUndo('add', ['note_id' => $duplicate->note_id], __('Note duplicated.'));
        $this->selectNote($duplicate->note_id, false);
    }

    private function cloneNoteRecursive(Note $source, ?string $newParentId, int $depth): Note
    {
        $clone = Note::create([
            'project_id'     => $this->project->project_id,
            'parent_note_id' => $newParentId,
            'depth'          => $depth,
            'sort_order'     => $this->nextSortOrder($newParentId),
            'title'          => $source->title . ' (Copy)',
            'body'           => $source->body,
        ]);
        foreach ($source->children as $child) {
            $this->cloneNoteRecursive($child, $clone->note_id, $depth + 1);
        }
        return $clone;
    }

    public function deleteNote(string $noteId): void
    {
        $note = Note::where('note_id', $noteId)
                    ->where('project_id', $this->project->project_id)
                    ->first();
        if (!$note) return;

        $wasActive = ($this->activeNoteId === $noteId);
        
        $this->pushUndo('remove', ['note_id' => $noteId], __('Note deleted.'));
        
        $note->delete();

        if ($wasActive) {
            $this->activeNoteId = null;
            $this->editorBody   = '';
            $next = Note::where('project_id', $this->project->project_id)
                        ->whereNull('parent_note_id')
                        ->orderBy('sort_order')
                        ->first();
            if ($next) {
                $this->selectNote($next->note_id, false);
            } else {
                $this->dispatch('refresh-editor-content');
            }
        }
    }

    public function saveCurrentBody(): void
    {
        if (!$this->activeNoteId) return;
        Note::where('note_id', $this->activeNoteId)
            ->where('project_id', $this->project->project_id)
            ->update(['body' => $this->editorBody]);
    }

    public function updateBody(string $html, ?int $cursorOffset = null): void
    {
        $normOld = trim(str_replace(['<p><br></p>', '<p></p>', '<br>', "\r", "\n", "\t"], '', $this->editorBody));
        $normNew = trim(str_replace(['<p><br></p>', '<p></p>', '<br>', "\r", "\n", "\t"], '', $html));

        if ($this->activeNoteId && $this->editorBody !== $html && $normOld !== $normNew) {
            $this->undoStack[] = [
                'action'    => 'edit',
                'data'      => [
                    'note_id'     => $this->activeNoteId,
                    'old_body'    => $this->editorBody,
                    'new_body'    => $html,
                    'old_cursor'  => $this->lastCursorOffset,
                    'new_cursor'  => $cursorOffset,
                ],
                'timestamp' => time(),
            ];
            $this->redoStack = [];
            $this->dispatch('show-undo-toast', message: __('Text edited.'));
        }
        $this->editorBody = $html;
        $this->lastCursorOffset = $cursorOffset;
        $this->saveCurrentBody();
    }

    public function saveUploadedFile()
    {
        if ($this->uploadFile) {
            $path = $this->uploadFile->store('notes_attachments', 'public');
            $this->uploadFile = null;
            return asset('storage/' . $path);
        }
        return null;
    }

    public function moveNote(string $draggedId, ?string $targetId, string $position): void
    {
        $dragged = Note::where('note_id', $draggedId)
                       ->where('project_id', $this->project->project_id)
                       ->first();
        if (!$dragged) return;

        if ($targetId && ($targetId === $draggedId || $this->isDescendant($draggedId, $targetId))) return;

        $oldPositions = Note::where('project_id', $this->project->project_id)
                            ->get(['note_id', 'parent_note_id', 'depth', 'sort_order'])
                            ->mapWithKeys(fn ($n) => [$n->note_id => [
                                'parent_note_id' => $n->parent_note_id,
                                'depth'          => $n->depth,
                                'sort_order'     => $n->sort_order,
                            ]])->toArray();

        $subtreeHeight = $this->subtreeHeight($dragged);

        if ($position === 'inside') {
            $target = Note::find($targetId);
            if (!$target || !$target->canHaveChildren()) return;

            $newDepth = $target->depth + 1;
            if ($newDepth + $subtreeHeight > Note::MAX_DEPTH) return;

            $dragged->parent_note_id = $target->note_id;
            $dragged->depth          = $newDepth;
            $dragged->sort_order     = $this->nextSortOrder($target->note_id);
            $dragged->save();

            $this->updateChildDepths($dragged);

        } else {
            $target = $targetId ? Note::find($targetId) : null;
            $newParentId = $target ? $target->parent_note_id : null;
            $newDepth    = $target ? $target->depth : 0;

            if ($newDepth + $subtreeHeight > Note::MAX_DEPTH) return;

            $siblings = Note::where('project_id', $this->project->project_id)
                            ->where('parent_note_id', $newParentId)
                            ->where('note_id', '!=', $draggedId)
                            ->orderBy('sort_order')
                            ->get();

            $newOrder = [];
            foreach ($siblings as $sib) {
                if ($target && $sib->note_id === $target->note_id && $position === 'before') {
                    $newOrder[] = $draggedId;
                }
                $newOrder[] = $sib->note_id;
                if ($target && $sib->note_id === $target->note_id && $position === 'after') {
                    $newOrder[] = $draggedId;
                }
            }
            if (!in_array($draggedId, $newOrder)) {
                $newOrder[] = $draggedId;
            }

            foreach ($newOrder as $idx => $id) {
                if ($id === $draggedId) {
                    $dragged->parent_note_id = $newParentId;
                    $dragged->depth          = $newDepth;
                    $dragged->sort_order     = $idx + 1;
                    $dragged->save();
                    $this->updateChildDepths($dragged);
                } else {
                    Note::where('note_id', $id)->update(['sort_order' => $idx + 1]);
                }
            }
        }

        $newPositions = Note::where('project_id', $this->project->project_id)
                            ->get(['note_id', 'parent_note_id', 'depth', 'sort_order'])
                            ->mapWithKeys(fn ($n) => [$n->note_id => [
                                'parent_note_id' => $n->parent_note_id,
                                'depth'          => $n->depth,
                                'sort_order'     => $n->sort_order,
                            ]])->toArray();

        if ($oldPositions !== $newPositions) {
            $this->pushUndo('move', [
                'moved_id'      => $draggedId,
                'old_positions' => $oldPositions,
                'new_positions' => $newPositions,
            ], __('Note moved.'));
            $this->selectNote($draggedId);
        }
    }

    private function subtreeHeight(Note $note): int
    {
        $children = Note::where('parent_note_id', $note->note_id)->get();
        if ($children->isEmpty()) return 0;

        $maxChildHeight = 0;
        foreach ($children as $child) {
            $maxChildHeight = max($maxChildHeight, $this->subtreeHeight($child));
        }
        return 1 + $maxChildHeight;
    }

    private function isDescendant(string $ancestorId, string $checkId): bool
    {
        $note = Note::find($checkId);
        while ($note && $note->parent_note_id) {
            if ($note->parent_note_id === $ancestorId) return true;
            $note = Note::find($note->parent_note_id);
        }
        return false;
    }

    private function updateChildDepths(Note $parent): void
    {
        foreach ($parent->children as $child) {
            $child->depth = $parent->depth + 1;
            $child->save();
            $this->updateChildDepths($child);
        }
    }

    public function with(): array
    {
        $rootNotes = Note::where('project_id', $this->project->project_id)
                         ->whereNull('parent_note_id')
                         ->with('childrenRecursive')
                         ->orderBy('sort_order')
                         ->get();

        $activeNote = $this->activeNoteId ? Note::find($this->activeNoteId) : null;

        return [
            'rootNotes'  => $rootNotes,
            'activeNote' => $activeNote,
        ];
    }
}; ?>

<div
    @keydown.window.ctrl.z="
        const tag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
        if(tag !== 'input' && tag !== 'textarea' && !$event.shiftKey) {
            $event.preventDefault();
            $dispatch('request-undo');
        }
    "
    @keydown.window.ctrl.shift.z="
        const tag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
        if(tag !== 'input' && tag !== 'textarea') {
            $event.preventDefault();
            $dispatch('request-redo');
        }
    "
    x-data="notesManager"
    class="h-full"
>
    <div class="p-4 lg:p-6 max-w-7xl mx-auto">

        {{-- Breadcrumb --}}
        <x-breadcrumb :items="[
            ['label' => __('Dashboard'), 'url' => route('dashboard')],
            ['label' => $project->title, 'url' => route('projects.show', $project), 'truncate' => true],
            ['label' => __('Notes')]
        ]" />

        <h2 class="text-web-heading-2 text-text-100 mb-6">{{ __('Project Notes') }}</h2>

        {{-- Main Container --}}
        <div class="border border-brand-150 rounded-xl shadow-sm overflow-hidden flex flex-col h-[calc(100vh-200px)]">

            @if($rootNotes->isEmpty())
                <div class="flex flex-1 min-h-0">
                    <div class="w-[220px] shrink-0 border-r border-brand-150 flex flex-col bg-brand-50">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-brand-150">
                            <span class="text-app-caption font-semibold text-text-60 uppercase tracking-widest">{{ __('Document Tabs') }}</span>
                            <button wire:click="addNote" class="w-5 h-5 flex items-center justify-center text-secondary-200 hover:text-secondary-100 transition-colors" title="{{ __('Add new note') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </button>
                        </div>
                        <div class="flex-1"></div>
                    </div>
                    <div class="flex-1 flex flex-col items-center justify-center gap-4 bg-bg-main">
                        <x-icons.no-notes class="w-28 h-24" />
                        <p class="text-app-body-medium text-text-60">{{ __('You Didn\'t Have Any Notes!') }}</p>
                    </div>
                </div>
            @else
                <div class="flex flex-1 min-h-0">

                    {{-- LEFT PANEL --}}
                    @include('livewire.projects.partials.note-sidebar')

                    {{-- RIGHT PANEL: Editor --}}
                    <div class="flex-1 flex flex-col min-w-0">

                        @if($activeNote)
                            <x-text-editor 
                                editorId="note-editor"
                                updateMethod="updateBody"
                                contentProp="editorBody"
                                counterType="notes"
                                wire:key="editor-{{ $activeNote->note_id }}"
                            />
                        @else
                            <div class="flex-1 flex items-center justify-center text-text-60 text-app-body-medium">
                                {{ __('Select a note to start editing') }}
                            </div>
                        @endif

                    </div>
                </div>
            @endif

            {{-- ============================================================
                 GLOBAL CONTEXT MENU UNTUK TABS
            ============================================================ --}}
            @include('livewire.projects.partials.note-context-menu')

        </div>
    </div>

    <x-confirm-dialog
        eventName="open-delete-note-dialog"
        title="{{ __('Delete Note?') }}"
        description="{{ __('Are you sure you want to permanently delete this note? All sub-tabs will also be deleted.') }}"
        confirmText="{{ __('Yes, Delete') }}"
        cancelText="{{ __('Cancel') }}"
        submitAction="deleteNote"
        btnColor="bg-danger-100 hover:bg-danger-100/90 text-white"
    >
        <x-slot:icon>
            <x-icons.delete class="w-15 h-15" />
        </x-slot:icon>
    </x-confirm-dialog>

    {{-- Undo Toast Notification --}}
    <div x-data="{ show: false, message: '', timeout: null }"
         @show-undo-toast.window="
            message = $event.detail.message;
            show = true;
            clearTimeout(timeout);
            timeout = setTimeout(() => show = false, 7000);
         "
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-4"
         class="fixed bottom-6 right-6 bg-brand-10 border border-brand-150 text-text-80 p-4 rounded-xl shadow-xl flex items-center gap-4 z-50 max-w-sm"
         style="display: none;"
    >
         <span x-text="message" class="text-app-body-medium font-medium flex-1"></span>
         <button @click="$dispatch('request-undo'); show = false" class="text-app-feature text-secondary-200 font-semibold hover:text-secondary-100 hover:bg-secondary-200/10 px-3 py-1.5 rounded-lg transition-colors border border-secondary-200">
             {{ __('Undo') }}
         </button>
         <button @click="show = false" class="text-text-60 hover:text-text-100 p-1 rounded-md hover:bg-black/5 transition-colors">
             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
         </button>
    </div>
</div>
