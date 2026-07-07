<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use App\Models\Note;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public Project $project;
    public ?string $activeNoteId = null;
    public string $editorBody = '';
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
        $siblingCount = Note::where('project_id', $this->project->project_id)
                            ->where('parent_note_id', $parentId)
                            ->count() + 1;

        if ($parentId === null) {
            return 'Notes ' . $siblingCount;
        }

        $parent = Note::find($parentId);
        if (!$parent) {
            return 'Notes ' . $siblingCount;
        }

        $numericPart = preg_replace('/^Notes\s+/', '', $parent->title);
        return 'Notes ' . $numericPart . '.' . $siblingCount;
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
            'action' => $action,
            'data'   => $data,
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
                    $this->activeNoteId = $next->note_id;
                    $this->editorBody = $next->body ?? '';
                }
            }
        } elseif ($action === 'remove') {
            $note = Note::withTrashed()->find($data['note_id']);
            if ($note) {
                $note->restore();
                $this->selectNote($note->note_id);
            }
        } elseif ($action === 'rename') {
            $note = Note::find($data['note_id']);
            if ($note) {
                $note->update(['title' => $data['old_title']]);
            }
        }
        
        $this->redoStack[] = $lastAction;
        $this->dispatch('show-undo-toast', message: 'Action undone.');
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
                $this->selectNote($note->note_id);
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
                        $this->activeNoteId = $next->note_id;
                        $this->editorBody = $next->body ?? '';
                    }
                }
            }
        } elseif ($action === 'rename') {
            $note = Note::find($data['note_id']);
            if ($note) {
                $note->update(['title' => $data['new_title']]);
            }
        }
        
        $this->undoStack[] = $lastAction;
        $this->dispatch('show-undo-toast', message: 'Action redone.');
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
        $this->pushUndo('add', ['note_id' => $note->note_id], 'Note created.');
        $this->selectNote($note->note_id);
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
        $this->pushUndo('add', ['note_id' => $note->note_id], 'Sub tab created.');
        $this->selectNote($note->note_id);
    }

    public function selectNote(string $noteId): void
    {
        $this->saveCurrentBody();
        $note = Note::find($noteId);
        if (!$note) return;
        $this->activeNoteId = $noteId;
        $this->editorBody   = $note->body ?? '';
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
        
        $this->pushUndo('rename', ['note_id' => $noteId, 'old_title' => $note->title, 'new_title' => $finalTitle], 'Note renamed.');
        
        $note->update(['title' => $finalTitle]);
    }

    public function duplicateNote(string $noteId): void
    {
        $source = Note::with('childrenRecursive')->find($noteId);
        if (!$source) return;
        $duplicate = $this->cloneNoteRecursive($source, $source->parent_note_id, $source->depth);
        $this->pushUndo('add', ['note_id' => $duplicate->note_id], 'Note duplicated.');
        $this->selectNote($duplicate->note_id);
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
        
        $this->pushUndo('remove', ['note_id' => $noteId], 'Note deleted.');
        
        $note->delete();

        if ($wasActive) {
            $this->activeNoteId = null;
            $this->editorBody   = '';
            $next = Note::where('project_id', $this->project->project_id)
                        ->whereNull('parent_note_id')
                        ->orderBy('sort_order')
                        ->first();
            if ($next) {
                $this->activeNoteId = $next->note_id;
                $this->editorBody   = $next->body ?? '';
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

    public function updateBody(string $html): void
    {
        $this->editorBody = $html;
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
        const isEditable = document.activeElement ? document.activeElement.isContentEditable : false;
        if(tag !== 'input' && tag !== 'textarea' && !isEditable && !$event.shiftKey) {
            $event.preventDefault();
            $wire.undo();
        }
    "
    @keydown.window.ctrl.shift.z="
        const tag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
        const isEditable = document.activeElement ? document.activeElement.isContentEditable : false;
        if(tag !== 'input' && tag !== 'textarea' && !isEditable) {
            $event.preventDefault();
            $wire.redo();
        }
    "
    x-data="{
        // ========================
        // TAB STATE
        // ========================
        openMenuId: null,
        menuNoteId: null,
        menuNoteTitle: '',
        menuNoteIsLeaf: false,
        menuPos: { top: 0, left: 0 },
        renamingId: null,
        renameValue: '',
        collapsed: {},


        toggleCollapse(id) {
            this.collapsed[id] = !this.collapsed[id];
        },
        isCollapsed(id) {
            return !!this.collapsed[id];
        },

        openMenu(id, title, isLeaf, triggerEl) {
            this.openMenuId = id;
            this.menuNoteId = id;
            this.menuNoteTitle = title;
            this.menuNoteIsLeaf = isLeaf;
            const rect = triggerEl.getBoundingClientRect();
            
            let top = rect.bottom;
            let left = rect.right - 176;
            
            // Adjust if menu would get cut off at the bottom
            if (top + 170 > window.innerHeight) {
                top = rect.top - 165; 
            }
            
            this.menuPos = {
                top: top,
                left: left
            };
        },
        closeMenu() {
            this.openMenuId = null;
        },

        startRename(id, currentTitle) {
            this.renamingId = id;
            this.renameValue = currentTitle;
            this.openMenuId = null;
            this.$nextTick(() => {
                const el = document.getElementById('rename_' + id);
                if (el) { el.focus(); el.select(); }
            });
        },
        commitRename(id) {
            if (this.renameValue.trim() !== '') {
                $wire.renameNote(id, this.renameValue);
            }
            this.renamingId = null;
        },

        // ========================
        // DRAG & DROP (TABS)
        // ========================
        draggedId: null,
        dragOverId: null,
        dragPos: null,

        onDragStart(id, event) {
            this.draggedId = id;
            event.dataTransfer.effectAllowed = 'move';
        },
        onDragOver(id, position, event) {
            if (this.draggedId === id) return;
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            this.dragOverId = id;
            this.dragPos = position;
        },
        onDrop(targetId, position, event) {
            event.preventDefault();
            if (!this.draggedId || this.draggedId === targetId) {
                this.draggedId = null; this.dragOverId = null; return;
            }
            $wire.moveNote(this.draggedId, targetId, position);
            this.draggedId = null;
            this.dragOverId = null;
            this.dragPos = null;
        },
        onDragEnd() {
            this.draggedId = null;
            this.dragOverId = null;
            this.dragPos = null;
        }
    }"
    class="h-full"
>
    <style>
        [x-cloak] { display: none !important; }

        .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: var(--color-secondary-100); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: var(--color-secondary-150); }

        .tab-item { transition: background-color 0.12s; }
        .tab-item:hover { background-color: var(--color-brand-150); }
        .tab-item.active { background-color: var(--color-brand-200); }

        .context-menu-item:hover { background-color: var(--color-brand-100); }

        .drag-over-before { box-shadow: inset 0 2px 0 0 var(--color-secondary-200); }
        .drag-over-after  { box-shadow: inset 0 -2px 0 0 var(--color-secondary-200); }
        .drag-over-inside { background-color: var(--color-brand-150) !important; border-radius: 4px; }
        .dragging-opacity { opacity: 0.4; }

        .subtab-line { position: absolute; left: 0; top: 0; bottom: 0; width: 1px; background-color: var(--color-brand-200); }
        .collapse-caret { transition: transform 0.15s ease; }
        .collapse-caret.is-collapsed { transform: rotate(-90deg); }

        .drag-handle { cursor: grab; opacity: 0; transition: opacity 0.12s; }
        .tab-item:hover .drag-handle { opacity: 1; }
        .drag-handle:active { cursor: grabbing; }
    </style>

    <div class="p-4 lg:p-6 max-w-7xl mx-auto">

        {{-- Breadcrumb --}}
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $project->title, 'url' => route('projects.show', $project), 'truncate' => true],
            ['label' => 'Notes']
        ]" />

        <h2 class="text-web-heading-2 text-text-100 mb-6">Project Notes</h2>

        {{-- Main Container --}}
        <div class="border border-brand-150 rounded-xl shadow-sm overflow-hidden flex flex-col h-[calc(100vh-200px)]">

            @if($rootNotes->isEmpty())
                <div class="flex flex-1 min-h-0">
                    <div class="w-[220px] shrink-0 border-r border-brand-150 flex flex-col bg-brand-50">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-brand-150">
                            <span class="text-app-caption font-semibold text-text-60 uppercase tracking-widest">Document Tabs</span>
                            <button wire:click="addNote" class="w-5 h-5 flex items-center justify-center text-secondary-200 hover:text-secondary-100 transition-colors" title="Add new note">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </button>
                        </div>
                        <div class="flex-1"></div>
                    </div>
                    <div class="flex-1 flex flex-col items-center justify-center gap-4 bg-bg-main">
                        <x-icons.no-notes class="w-28 h-24" />
                        <p class="text-app-body-medium text-text-60">You Didn't Have Any Notes!</p>
                    </div>
                </div>
            @else
                <div class="flex flex-1 min-h-0">

                    {{-- LEFT PANEL --}}
                    <div id="tab-panel" class="w-[220px] shrink-0 border-r border-brand-150 flex flex-col bg-brand-50 relative">

                        <div class="flex items-center justify-between px-4 py-3 border-b border-brand-150">
                            <span class="text-app-caption font-semibold text-text-60 uppercase tracking-widest">Document Tabs</span>
                            <button wire:click="addNote" class="w-5 h-5 flex items-center justify-center text-secondary-200 hover:text-secondary-100 transition-colors" title="Add new note">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </button>
                        </div>

                        {{-- Tab list --}}
                        <div class="flex-1 overflow-y-auto py-2 custom-scrollbar">
                            @foreach($rootNotes as $note)
                                @include('livewire.projects.partials.note-tab-item', [
                                    'note'         => $note,
                                    'activeNoteId' => $activeNoteId,
                                    'depth'        => 0,
                                ])
                            @endforeach

                            <div
                                class="h-10"
                                x-bind:class="dragOverId === '__root__' ? 'drag-over-after' : ''"
                                @dragover.prevent="if (draggedId) { dragOverId = '__root__'; dragPos = 'after'; }"
                                @dragleave="if (dragOverId === '__root__') dragOverId = null"
                                @drop.prevent="if (draggedId) { $wire.moveNote(draggedId, null, 'after'); draggedId = null; dragOverId = null; }"
                            ></div>
                        </div>

                    </div>

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
                                Select a note to start editing
                            </div>
                        @endif

                    </div>
                </div>
            @endif

            {{-- ============================================================
                 GLOBAL CONTEXT MENU UNTUK TABS
            ============================================================ --}}
            <template x-teleport="body">
                <div
                    data-menu-dropdown
                    x-show="openMenuId !== null"
                    x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    @click.outside="closeMenu()"
                    x-bind:style="`position: fixed; top: ${menuPos.top}px; left: ${menuPos.left}px; z-index: 9999;`"
                    class="w-48 bg-white border border-brand-150 rounded-lg shadow-lg py-1 overflow-hidden z-50"
                    style="display: none;"
                >
                    <button
                        x-show="!menuNoteIsLeaf"
                        class="w-full text-left px-4 py-2 text-app-body-medium text-text-80 hover:bg-brand-10 flex items-center gap-3 transition-colors"
                        @click="closeMenu(); $wire.addSubTab(menuNoteId)"
                    >
                        <x-icons.add class="w-4 h-4 shrink-0 text-text-80" />
                        Add Sub-Tab
                    </button>

                    <button
                        class="w-full text-left px-4 py-2 text-app-body-medium text-text-80 hover:bg-brand-10 flex items-center gap-3 transition-colors"
                        @click="closeMenu(); startRename(menuNoteId, menuNoteTitle)"
                    >
                        <x-icons.rename class="w-4 h-4 shrink-0 text-text-80" />
                        Rename
                    </button>

                    <button
                        class="w-full text-left px-4 py-2 text-app-body-medium text-text-80 hover:bg-brand-10 flex items-center gap-3 transition-colors"
                        @click="closeMenu(); $wire.duplicateNote(menuNoteId)"
                    >
                        <x-icons.duplicate class="w-4 h-4 shrink-0 text-text-80" />
                        Duplicate
                    </button>

                    <div class="h-px bg-brand-150 my-1"></div>

                    <button
                        class="w-full text-left px-4 py-2 text-app-body-medium text-danger-100 hover:bg-danger-100/5 flex items-center gap-3 transition-colors"
                        @click="
                            closeMenu();
                            $dispatch('open-delete-note-dialog', { id: menuNoteId });
                        "
                    >
                        <x-icons.delete class="w-4 h-4 shrink-0 text-danger-100" />
                        Delete
                    </button>
                </div>
            </template>

        </div>
    </div>

    <x-confirm-dialog
        eventName="open-delete-note-dialog"
        title="Delete Note?"
        description="Are you sure you want to permanently delete this note? All sub-tabs will also be deleted."
        confirmText="Yes, Delete"
        cancelText="Cancel"
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
         <button @click="$wire.undo(); show = false" class="text-app-feature text-secondary-200 font-semibold hover:text-secondary-100 hover:bg-secondary-200/10 px-3 py-1.5 rounded-lg transition-colors border border-secondary-200">
             Undo
         </button>
         <button @click="show = false" class="text-text-60 hover:text-text-100 p-1 rounded-md hover:bg-black/5 transition-colors">
             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
         </button>
    </div>
</div>
