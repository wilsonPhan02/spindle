<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Note;
use App\Models\Project;

new #[Layout('layouts.app')] class extends Component {

    public Project $project;
    public ?string $activeNoteId = null;
    public string $editorBody = '';

    public function mount(Project $project): void
    {
        $this->project = $project;
        $first = Note::where('project_id', $project->project_id)
                     ->whereNull('parent_note_id')
                     ->orderBy('sort_order')
                     ->first();
        if ($first) {
            $this->activeNoteId = $first->note_id;
            $this->editorBody   = $first->body ?? '';
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
    // Actions
    // -----------------------------------------------------------------------

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
        if ($trimmed === '') return;
        $note->update(['title' => $trimmed]);
    }

    public function duplicateNote(string $noteId): void
    {
        $source = Note::with('childrenRecursive')->find($noteId);
        if (!$source) return;
        $duplicate = $this->cloneNoteRecursive($source, $source->parent_note_id, $source->depth);
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

        // PERBAIKAN: Fungsi Menu Global dipusatkan di sini
        openMenu(id, title, isLeaf, triggerEl) {
            this.openMenuId = id;
            this.menuNoteId = id;
            this.menuNoteTitle = title;
            this.menuNoteIsLeaf = isLeaf;
            const rect = triggerEl.getBoundingClientRect();
            this.menuPos = {
                top:   rect.top + window.scrollY,
                left:  rect.right + window.scrollX - 176
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
        // DRAG & DROP
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
        },

        // ========================
        // EDITOR TOOLBAR
        // ========================
        formatValue: 'p',
        formatLabel: 'Normal Text',
        activeStates: {
            bold: false, italic: false, underline: false, strikeThrough: false,
            insertUnorderedList: false, insertOrderedList: false,
            justifyLeft: true, justifyCenter: false, justifyRight: false, justifyFull: false,
        },

        currentTextColor: '#2c2c2c',
        currentHighlightColor: '#ffeaa7',
        textColors: ['#2c2c2c', '#8c7558', '#b23a3a', '#2563eb', '#15803d', '#9333ea', '#ea580c'],
        highlightColors: ['#ffeaa7', '#ffd1dc', '#c7f0db', '#cde7ff', '#e8d9ff', '#ffe0b3', 'transparent'],
        savedRange: null,

        isDraggingToolbar: false,
        startX: 0,
        scrollLeft: 0,

        onToolbarMousedown(e) {
            if (!e.target.closest('button')) {
                e.preventDefault();
                this.saveSelection();
                this.isDraggingToolbar = true;
                this.startX = e.pageX - this.$refs.toolbar.offsetLeft;
                this.scrollLeft = this.$refs.toolbar.scrollLeft;
            }
        },
        onToolbarMousemove(e) {
            if (!this.isDraggingToolbar) return;
            e.preventDefault();
            const x = e.pageX - this.$refs.toolbar.offsetLeft;
            const walk = (x - this.startX) * 1.5;
            this.$refs.toolbar.scrollLeft = this.scrollLeft - walk;
        },
        onToolbarWheel(e) {
            if (e.deltaY !== 0) {
                e.preventDefault();
                this.$refs.toolbar.scrollLeft += e.deltaY;
            }
        },

        saveSelection() {
            const editorEl = document.getElementById('note-editor');
            const sel = window.getSelection();
            if (sel && sel.rangeCount > 0 && editorEl && editorEl.contains(sel.anchorNode)) {
                this.savedRange = sel.getRangeAt(0).cloneRange();
            }
        },
        restoreSelection() {
            const editorEl = document.getElementById('note-editor');
            if (!editorEl) return;
            editorEl.focus();
            if (this.savedRange) {
                const sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(this.savedRange);
            }
        },

        exec(cmd, val = null) {
            const editorEl = document.getElementById('note-editor');
            if (document.activeElement !== editorEl) {
                this.restoreSelection();
            }
            document.execCommand(cmd, false, val);
            this.refreshToolbarState();
            this.scheduleSave();
        },
        setFormat(tag, label) {
            const editorEl = document.getElementById('note-editor');
            if (document.activeElement !== editorEl) {
                this.restoreSelection();
            }
            document.execCommand('formatBlock', false, tag === 'p' ? 'p' : tag);
            this.formatValue = tag;
            this.formatLabel = label;
            this.scheduleSave();
        },
        refreshToolbarState() {
            try {
                this.activeStates.bold = document.queryCommandState('bold');
                this.activeStates.italic = document.queryCommandState('italic');
                this.activeStates.underline = document.queryCommandState('underline');
                this.activeStates.strikeThrough = document.queryCommandState('strikeThrough');
                this.activeStates.insertUnorderedList = document.queryCommandState('insertUnorderedList');
                this.activeStates.insertOrderedList = document.queryCommandState('insertOrderedList');
                this.activeStates.justifyLeft = document.queryCommandState('justifyLeft');
                this.activeStates.justifyCenter = document.queryCommandState('justifyCenter');
                this.activeStates.justifyRight = document.queryCommandState('justifyRight');
                this.activeStates.justifyFull = document.queryCommandState('justifyFull');

                const sel = window.getSelection();
                if (sel && sel.anchorNode) {
                    let node = sel.anchorNode;
                    if (node.nodeType === 3) node = node.parentElement;
                    const block = node ? node.closest('h1, h2, h3, blockquote, p') : null;
                    if (block) {
                        const tag = block.tagName.toLowerCase();
                        const map = { p: 'Normal Text', h1: 'Heading 1', h2: 'Heading 2', h3: 'Heading 3', blockquote: 'Quote' };
                        this.formatValue = tag;
                        this.formatLabel = map[tag] || 'Normal Text';
                    }
                }
            } catch (e) { /* ignore */ }
        },

        scheduleSave() {
            const editorEl = document.getElementById('note-editor');
            if (!editorEl) return;
            this.updateCharCount();
            clearTimeout(this._saveTimer);
            this._saveTimer = setTimeout(() => {
                $wire.updateBody(editorEl.innerHTML);
            }, 600);
        },
        updateCharCount() {
            const el = document.getElementById('note-editor');
            if (!el) return;
            const text = (el.innerText || '').replace(/\u200B/g, '').trim();
            const len = text.length;
            const words = text === '' ? 0 : text.split(/\s+/).filter(Boolean).length;
            const counter = document.getElementById('char-counter');
            if (counter) {
                counter.textContent = len.toLocaleString() + ' / 65,535 • ' + words + ' words';
                counter.classList.toggle('text-red-500', len > 65000);
                counter.classList.toggle('text-[#A08866]', len <= 65000);
            }
        },

        handleMarkdownShortcuts() {
            const sel = window.getSelection();
            if (!sel || sel.rangeCount === 0) return;
            const node = sel.anchorNode;

            if (node.nodeType !== Node.TEXT_NODE) return;

            let text = node.textContent;
            let textBeforeCursor = text.slice(0, sel.anchorOffset);
            let cleanedBeforeCursor = textBeforeCursor.replace(/\u200B/g, '');

            const blockPatterns = [
                { regex: /^#\s$/, cmd: 'formatBlock', val: 'h1' },
                { regex: /^##\s$/, cmd: 'formatBlock', val: 'h2' },
                { regex: /^###\s$/, cmd: 'formatBlock', val: 'h3' },
                { regex: /^>\s$/, cmd: 'formatBlock', val: 'blockquote' },
                { regex: /^-\s$/, cmd: 'insertUnorderedList' },
                { regex: /^\*\s$/, cmd: 'insertUnorderedList' },
                { regex: /^\+\s$/, cmd: 'insertUnorderedList' },
                { regex: /^1\.\s$/, cmd: 'insertOrderedList' },
                { regex: /^a\.\s$/, cmd: 'insertOrderedList', callback: () => {
                    setTimeout(() => {
                        const currNode = window.getSelection().anchorNode;
                        const targetElem = currNode && currNode.nodeType === 3 ? currNode.parentElement : currNode;
                        const ol = targetElem ? targetElem.closest('ol') : null;
                        if (ol) {
                            ol.setAttribute('type', 'a');
                            ol.style.listStyleType = 'lower-alpha';
                        }
                    }, 10);
                }},
                { regex: /^---\s$/, cmd: 'insertHorizontalRule' }
            ];

            for (let pattern of blockPatterns) {
                if (pattern.regex.test(cleanedBeforeCursor)) {
                    const range = document.createRange();
                    range.setStart(node, 0);
                    range.setEnd(node, sel.anchorOffset);
                    sel.removeAllRanges();
                    sel.addRange(range);

                    document.execCommand('delete', false, null);

                    if (pattern.cmd) document.execCommand(pattern.cmd, false, pattern.val);
                    if (pattern.callback) pattern.callback();

                    this.refreshToolbarState();
                    this.scheduleSave();
                    return;
                }
            }

            const inlinePatterns = [
                { regex: /\*\*([^*]+)\*\*\s$/, tag: 'b' },
                { regex: /\*([^*]+)\*\s$/, tag: 'i' },
                { regex: /~~([^~]+)~~\s$/, tag: 'strike' },
                { regex: /==([^=]+)==\s$/, tag: 'mark', useCurrentColor: true }
            ];

            for (let pattern of inlinePatterns) {
                const match = cleanedBeforeCursor.match(pattern.regex);
                if (match && match.index + match[0].length === cleanedBeforeCursor.length) {
                    const fullMatchText = match[0];
                    const innerText = match[1];

                    const range = document.createRange();
                    range.setStart(node, sel.anchorOffset - fullMatchText.length);
                    range.setEnd(node, sel.anchorOffset);
                    sel.removeAllRanges();
                    sel.addRange(range);

                    const styleAttr = pattern.useCurrentColor && this.currentHighlightColor !== 'transparent' ? ` style='background-color: ${this.currentHighlightColor};'` : '';
                    const htmlToInsert = `<${pattern.tag}${styleAttr}>${innerText}</${pattern.tag}>&nbsp;`;

                    document.execCommand('insertHTML', false, htmlToInsert);

                    this.refreshToolbarState();
                    this.scheduleSave();
                    return;
                }
            }
        },

        initEditorElement() {
            const editorEl = document.getElementById('note-editor');
            if (!editorEl || editorEl.dataset.boundListeners === '1') return;
            editorEl.dataset.boundListeners = '1';

            editorEl.innerHTML = (window.__initialEditorBody ?? '') || '';
            this.updateCharCount();

            editorEl.addEventListener('input', () => this.scheduleSave());

            editorEl.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    e.preventDefault();

                    const sel = window.getSelection();
                    const node = sel.anchorNode;
                    const targetElem = node ? (node.nodeType === 3 ? node.parentElement : node) : null;
                    const parentLi = targetElem ? targetElem.closest('li') : null;

                    if (parentLi) {
                        if (e.shiftKey) {
                            document.execCommand('outdent', false, null);
                        } else {
                            document.execCommand('indent', false, null);
                        }
                    }
                    else {
                        document.execCommand('insertHTML', false, '&nbsp;&nbsp;&nbsp;&nbsp;');
                    }
                    this.scheduleSave();
                }
                else if (e.key === 'Enter' && !e.shiftKey) {
                    setTimeout(() => {
                        const sel = window.getSelection();
                        if (sel && sel.anchorNode) {
                            let node = sel.anchorNode;
                            if (node.nodeType === 3) node = node.parentElement;
                            const block = node ? node.closest('h1, h2, h3, blockquote') : null;

                            if (block) {
                                document.execCommand('formatBlock', false, 'p');
                            }
                        }
                        this.formatValue = 'p';
                        this.formatLabel = 'Normal Text';
                        this.refreshToolbarState();
                        this.saveSelection();
                    }, 0);
                }
                else {
                    setTimeout(() => this.refreshToolbarState(), 0);
                }
            });

            editorEl.addEventListener('keyup', (e) => {
                if (e.key === ' ') {
                    this.handleMarkdownShortcuts();
                }
                if (e.key !== 'Enter' && e.key !== 'Tab') {
                    this.refreshToolbarState();
                    this.saveSelection();
                }
            });
            editorEl.addEventListener('mouseup', () => { this.refreshToolbarState(); this.saveSelection(); });
            editorEl.addEventListener('focus', () => this.refreshToolbarState());
            editorEl.addEventListener('mousedown', () => this.saveSelection());

            document.addEventListener('selectionchange', () => {
                try {
                    const sel = window.getSelection();
                    if (sel && sel.rangeCount > 0 && editorEl.contains(sel.anchorNode)) {
                        this.savedRange = sel.getRangeAt(0).cloneRange();
                    }
                } catch (e) { /* ignore */ }
            });
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

        #note-editor:focus { outline: none; }
        #note-editor p { margin-bottom: 0.75em; }

        #note-editor h1 { font-size: 2em; font-weight: normal; margin-bottom: 0.5em; }
        #note-editor h2 { font-size: 1.5em; font-weight: normal; margin-bottom: 0.5em; }
        #note-editor h3 { font-size: 1.17em; font-weight: normal; margin-bottom: 0.5em; }

        #note-editor h1, #note-editor h2, #note-editor h3,
        #note-editor h1 *, #note-editor h2 *, #note-editor h3 * {
            font-weight: normal !important;
        }

        #note-editor ul { list-style-type: disc; padding-left: 1.5em; }
        #note-editor ul ul { list-style-type: circle; }
        #note-editor ul ul ul { list-style-type: square; }
        #note-editor ul ul ul ul { list-style-type: disc; }
        #note-editor ul ul ul ul ul { list-style-type: circle; }
        #note-editor ul ul ul ul ul ul { list-style-type: square; }

        #note-editor ol { list-style: decimal; padding-left: 1.5em; }
        #note-editor hr { border: none; border-top: 1px solid #D5C6A9; margin: 1em 0; }
        #note-editor blockquote { border-left: 3px solid #D5C6A9; padding-left: 1em; color: #7A7A7A; }

        .toolbar-scroll::-webkit-scrollbar { display: none; }
        .toolbar-scroll { -ms-overflow-style: none; scrollbar-width: none; cursor: default; }

        .tab-item { transition: background-color 0.12s; }
        .tab-item:hover { background-color: #EAE1D5; }
        .tab-item.active { background-color: #E0D5C5; }

        .context-menu-item:hover { background-color: #F0E8DC; }

        .toolbar-btn {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            width: 28px; height: 28px; border-radius: 5px;
            cursor: pointer; color: #5A5A5A; flex-shrink: 0;
            transition: background-color 0.1s, color 0.1s;
            border: 1px solid transparent;
        }
        .toolbar-btn:hover { background-color: #EAE1D5; }
        .toolbar-btn.is-active {
            background-color: #E0D0B0;
            color: #5E4C38;
            border-color: #C4A877;
        }
        .toolbar-divider { width: 1px; height: 18px; background-color: #D5C6A9; flex-shrink: 0; margin: 0 2px; }

        .format-option.bg-\[\#EAE1D5\] { background-color: #EAE1D5; }

        .drag-over-before { box-shadow: inset 0 2px 0 0 #8C7558; }
        .drag-over-after  { box-shadow: inset 0 -2px 0 0 #8C7558; }
        .drag-over-inside { background-color: #EAE1D5 !important; border-radius: 4px; }
        .dragging-opacity { opacity: 0.4; }

        .subtab-line { position: absolute; left: 0; top: 0; bottom: 0; width: 1px; background-color: #D5C6A9; }
        .collapse-caret { transition: transform 0.15s ease; }
        .collapse-caret.is-collapsed { transform: rotate(-90deg); }

        .drag-handle { cursor: grab; opacity: 0; transition: opacity 0.12s; }
        .tab-item:hover .drag-handle { opacity: 1; }
        .drag-handle:active { cursor: grabbing; }
    </style>

    <div class="p-6 lg:p-10 max-w-7xl mx-auto">

        {{-- Breadcrumb --}}
        <header class="flex justify-between items-center mb-8">
            <div class="flex items-center gap-3 text-[18px] text-[#7A7A7A]">
                <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-[#8C7558] transition-colors">Dashboard</a>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ route('projects.show', $project) }}" wire:navigate class="hover:text-[#8C7558] transition-colors truncate max-w-[160px]">{{ $project->title }}</a>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-[#2C2C2C] font-semibold">Notes</span>
            </div>
            <x-logo class="h-8 w-auto text-text-100" />
        </header>

        <h2 class="text-[28px] font-merriweather text-[#2C2C2C] mb-6">Project Notes</h2>

        {{-- Main Container --}}
        <div class="border border-[#E8DED2] rounded-xl shadow-sm overflow-hidden" style="min-height: 500px;">

            @if($rootNotes->isEmpty())
                <div class="flex" style="min-height: 500px;">
                    <div class="w-[220px] shrink-0 border-r border-[#E8DED2] flex flex-col bg-[#FAF7F3]">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-[#E8DED2]">
                            <span class="text-[11px] font-semibold text-[#9A8E80] uppercase tracking-widest">Document Tabs</span>
                            <button wire:click="addNote" class="w-5 h-5 flex items-center justify-center text-[#8C7558] hover:text-[#5E4C38] transition-colors" title="Add new note">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </button>
                        </div>
                        <div class="flex-1"></div>
                    </div>
                    <div class="flex-1 flex flex-col items-center justify-center gap-4 bg-white">
                        <x-icons.no-notes class="w-28 h-24" />
                        <p class="text-[14px] text-[#B0A090] font-medium">You Didn't Have Any Notes!</p>
                    </div>
                </div>
            @else
                <div class="flex h-full" style="min-height: 500px;">

                    {{-- LEFT PANEL --}}
                    <div id="tab-panel" class="w-[220px] shrink-0 border-r border-[#E8DED2] flex flex-col bg-[#FAF7F3] relative">

                        <div class="flex items-center justify-between px-4 py-3 border-b border-[#E8DED2]">
                            <span class="text-[11px] font-semibold text-[#9A8E80] uppercase tracking-widest">Document Tabs</span>
                            <button wire:click="addNote" class="w-5 h-5 flex items-center justify-center text-[#8C7558] hover:text-[#5E4C38] transition-colors" title="Add new note">
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
                            {{-- Toolbar --}}
                            <div
                                class="flex items-center px-3 py-2 border-b border-[#E8DED2] bg-[#FAF7F3] gap-1 toolbar-scroll overflow-x-auto"
                                x-ref="toolbar"
                                @mousedown="onToolbarMousedown($event)"
                                @mouseleave="isDraggingToolbar = false"
                                @mouseup="isDraggingToolbar = false"
                                @mousemove="onToolbarMousemove($event)"
                                @wheel="onToolbarWheel($event)"
                            >

                                {{-- Format dropdown --}}
                                <div class="relative shrink-0" x-data="{ open: false, pos: { top: 0, left: 0 } }">
                                    <button
                                        type="button"
                                        @mousedown.prevent="saveSelection()"
                                        @click.stop="
                                            const r = $event.currentTarget.getBoundingClientRect();
                                            pos = { top: r.bottom + window.scrollY + 4, left: r.left + window.scrollX };
                                            open = !open;
                                        "
                                        class="h-7 px-2.5 flex items-center gap-1.5 text-[12px] text-[#4A4A4A] bg-white border border-[#E0D5C5] rounded-md outline-none cursor-pointer hover:border-[#B69F78] transition-colors"
                                        style="min-width: 118px;"
                                        title="Text Format"
                                    >
                                        <span x-text="formatLabel" class="flex-1 text-left"></span>
                                        <svg class="w-3 h-3 text-[#9A8E80] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <template x-teleport="body">
                                        <div
                                            x-show="open"
                                            x-cloak
                                            x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="opacity-0 scale-95"
                                            x-transition:enter-end="opacity-100 scale-100"
                                            @click.outside="open = false"
                                            x-bind:style="`position: fixed; top: ${pos.top}px; left: ${pos.left}px; z-index: 9999;`"
                                            class="w-40 bg-white border border-[#E0D5C5] rounded-lg shadow-lg py-1"
                                            style="display: none;"
                                        >
                                            <button type="button" @mousedown.prevent="saveSelection()" @click="setFormat('p', 'Normal Text'); open = false" class="format-option w-full text-left px-3 py-1.5 text-[13px] text-[#2C2C2C] hover:bg-[#F0E8DC]" x-bind:class="formatValue === 'p' ? 'bg-[#EAE1D5] font-semibold' : ''" title="Normal Text">Normal Text</button>
                                            <button type="button" @mousedown.prevent="saveSelection()" @click="setFormat('h1', 'Heading 1'); open = false" class="format-option w-full text-left px-3 py-1.5 text-[18px] font-bold text-[#2C2C2C] hover:bg-[#F0E8DC]" x-bind:class="formatValue === 'h1' ? 'bg-[#EAE1D5]' : ''" title="Heading 1 (# + Space)">Heading 1</button>
                                            <button type="button" @mousedown.prevent="saveSelection()" @click="setFormat('h2', 'Heading 2'); open = false" class="format-option w-full text-left px-3 py-1.5 text-[15px] font-bold text-[#2C2C2C] hover:bg-[#F0E8DC]" x-bind:class="formatValue === 'h2' ? 'bg-[#EAE1D5]' : ''" title="Heading 2 (## + Space)">Heading 2</button>
                                            <button type="button" @mousedown.prevent="saveSelection()" @click="setFormat('h3', 'Heading 3'); open = false" class="format-option w-full text-left px-3 py-1.5 text-[13.5px] font-bold text-[#2C2C2C] hover:bg-[#F0E8DC]" x-bind:class="formatValue === 'h3' ? 'bg-[#EAE1D5]' : ''" title="Heading 3 (### + Space)">Heading 3</button>
                                            <button type="button" @mousedown.prevent="saveSelection()" @click="setFormat('blockquote', 'Quote'); open = false" class="format-option w-full text-left px-3 py-1.5 text-[13px] italic text-[#6B6B6B] hover:bg-[#F0E8DC]" x-bind:class="formatValue === 'blockquote' ? 'bg-[#EAE1D5]' : ''" title="Quote (> + Space)">Quote</button>
                                        </div>
                                    </template>
                                </div>

                                <div class="toolbar-divider"></div>

                                <button type="button" data-cmd="bold" @mousedown.prevent="saveSelection()" @click="exec('bold')" class="toolbar-btn" x-bind:class="activeStates.bold ? 'is-active' : ''" title="Bold (Ctrl+B or **text**)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M15.6 10.79c.97-.67 1.65-1.77 1.65-2.79 0-2.26-1.75-4-4-4H7v14h7.04c2.09 0 3.71-1.7 3.71-3.79 0-1.52-.86-2.82-2.15-3.42zM10 6.5h3c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-3v-3zm3.5 9H10v-3h3.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5z"/></svg>
                                </button>
                                <button type="button" data-cmd="italic" @mousedown.prevent="saveSelection()" @click="exec('italic')" class="toolbar-btn" x-bind:class="activeStates.italic ? 'is-active' : ''" title="Italic (Ctrl+I or *text*)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M10 4v3h2.21l-3.42 8H6v3h8v-3h-2.21l3.42-8H18V4z"/></svg>
                                </button>
                                <button type="button" data-cmd="underline" @mousedown.prevent="saveSelection()" @click="exec('underline')" class="toolbar-btn" x-bind:class="activeStates.underline ? 'is-active' : ''" title="Underline (Ctrl+U)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17c3.31 0 6-2.69 6-6V3h-2.5v8c0 1.93-1.57 3.5-3.5 3.5S8.5 12.93 8.5 11V3H6v8c0 3.31 2.69 6 6 6zm-7 2v2h14v-2H5z"/></svg>
                                </button>
                                <button type="button" data-cmd="strikeThrough" @mousedown.prevent="saveSelection()" @click="exec('strikeThrough')" class="toolbar-btn" x-bind:class="activeStates.strikeThrough ? 'is-active' : ''" title="Strikethrough (~~text~~)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M10 19h4v-3h-4v3zM5 4v3h5v3h4V7h5V4H5zM3 14h18v-2H3v2z"/></svg>
                                </button>

                                <div class="toolbar-divider"></div>

                                <button type="button" @mousedown.prevent="saveSelection()" @click="exec('insertUnorderedList')" class="toolbar-btn" x-bind:class="activeStates.insertUnorderedList ? 'is-active' : ''" title="Bullet List (-, *, or + and Space)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M4 10.5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm0-6c-.83 0-1.5.67-1.5 1.5S3.17 7.5 4 7.5 5.5 6.83 5.5 6 4.83 4.5 4 4.5zm0 12c-.83 0-1.5.68-1.5 1.5s.68 1.5 1.5 1.5 1.5-.68 1.5-1.5-.67-1.5-1.5-1.5zM7 19h14v-2H7v2zm0-6h14v-2H7v2zm0-8v2h14V5H7z"/></svg>
                                </button>
                                <button type="button" @mousedown.prevent="saveSelection()" @click="exec('insertOrderedList')" class="toolbar-btn" x-bind:class="activeStates.insertOrderedList ? 'is-active' : ''" title="Numbered List (1. or a. and Space)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M2 17h2v.5H3v1h1v.5H2v1h3v-4H2v1zm1-9h1V4H2v1h1v3zm-1 3h1.8L2 13.1v.9h3v-1H3.2L5 10.9V10H2v1zm5-6v2h14V5H7zm0 14h14v-2H7v2zm0-6h14v-2H7v2z"/></svg>
                                </button>

                                <div class="toolbar-divider"></div>

                                <button type="button" @mousedown.prevent="saveSelection()" @click="exec('justifyLeft')" class="toolbar-btn" x-bind:class="activeStates.justifyLeft ? 'is-active' : ''" title="Align Left">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M15 15H3v2h12v-2zm0-8H3v2h12V7zM3 13h18v-2H3v2zm0 8h18v-2H3v2zM3 3v2h18V3H3z"/></svg>
                                </button>
                                <button type="button" @mousedown.prevent="saveSelection()" @click="exec('justifyCenter')" class="toolbar-btn" x-bind:class="activeStates.justifyCenter ? 'is-active' : ''" title="Align Center">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M7 15v2h10v-2H7zm-4 6h18v-2H3v2zm0-8h18v-2H3v2zm4-6v2h10V7H7zM3 3v2h18V3H3z"/></svg>
                                </button>
                                <button type="button" @mousedown.prevent="saveSelection()" @click="exec('justifyRight')" class="toolbar-btn" x-bind:class="activeStates.justifyRight ? 'is-active' : ''" title="Align Right">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3 21h18v-2H3v2zm6-4h12v-2H9v2zm-6-4h18v-2H3v2zm6-4h12V7H9v2zM3 3v2h18V3H3z"/></svg>
                                </button>
                                <button type="button" @mousedown.prevent="saveSelection()" @click="exec('justifyFull')" class="toolbar-btn" x-bind:class="activeStates.justifyFull ? 'is-active' : ''" title="Justify">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3 21h18v-2H3v2zm0-4h18v-2H3v2zm0-4h18v-2H3v2zm0-4h18V7H3v2zm0-6v2h18V3H3z"/></svg>
                                </button>

                                <div class="toolbar-divider"></div>

                                {{-- Text Color with Custom Color Picker --}}
                                <div class="relative" x-data="{ open: false, pos: { top: 0, left: 0 } }">
                                    <button
                                        type="button"
                                        @mousedown.prevent="saveSelection()"
                                        @click.stop="
                                            const r = $event.currentTarget.getBoundingClientRect();
                                            pos = { top: r.bottom + window.scrollY + 4, left: r.left + window.scrollX };
                                            open = !open;
                                        "
                                        class="toolbar-btn" title="Text Color"
                                    >
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M11 3L5.5 17h2.25l1.12-3h6.25l1.12 3h2.25L13 3h-2zm-1.38 9L12 5.67 14.38 12H9.62z"/></svg>
                                        <span class="block h-[3px] w-3.5 rounded-sm -mt-0.5" x-bind:style="`background:${currentTextColor}`"></span>
                                    </button>
                                    <template x-teleport="body">
                                        <div
                                            x-show="open" x-cloak x-transition
                                            @click.outside="open = false"
                                            x-bind:style="`position: fixed; top: ${pos.top}px; left: ${pos.left}px; z-index: 9999;`"
                                            class="p-2.5 bg-white border border-[#E0D5C5] rounded-lg shadow-lg flex flex-col"
                                            style="display: none; width: 172px;"
                                        >
                                            <span class="text-[10px] font-semibold text-[#9A8E80] uppercase tracking-wider mb-2 block">Theme Colors</span>
                                            <div class="grid grid-cols-7 gap-1.5 mb-2.5">
                                                <template x-for="c in textColors" :key="c">
                                                    <button type="button" @mousedown.prevent="saveSelection()" @click="exec('foreColor', c); currentTextColor = c; open = false" class="w-5 h-5 rounded-full border border-[#E0D5C5] hover:scale-110 transition-transform" x-bind:style="`background:${c}`" :title="c"></button>
                                                </template>
                                            </div>

                                            <div class="h-px bg-[#E8DED2] w-full mb-2.5"></div>

                                            <div class="flex items-center justify-between">
                                                <span class="text-[11.5px] text-[#5A5A5A] font-medium">Custom Color</span>
                                                <div class="relative w-5 h-5 rounded-full border border-[#E0D5C5] overflow-hidden cursor-pointer hover:scale-110 transition-transform shrink-0" title="Pick custom color">
                                                    <input type="color"
                                                           :value="currentTextColor"
                                                           @click.stop="saveSelection()"
                                                           @change="currentTextColor = $event.target.value; exec('foreColor', $event.target.value); open = false;"
                                                           class="absolute opacity-0 w-full h-full cursor-pointer z-10"
                                                           style="transform: scale(2);"
                                                    >
                                                    <div class="absolute inset-0 pointer-events-none" :style="`background-color: ${currentTextColor}`"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                {{-- Highlight Color with Custom Color Picker --}}
                                <div class="relative" x-data="{ open: false, pos: { top: 0, left: 0 } }">
                                    <button
                                        type="button"
                                        @mousedown.prevent="saveSelection()"
                                        @click.stop="
                                            const r = $event.currentTarget.getBoundingClientRect();
                                            pos = { top: r.bottom + window.scrollY + 4, left: r.left + window.scrollX };
                                            open = !open;
                                        "
                                        class="toolbar-btn" title="Highlight (==text==)"
                                    >
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M7 22l4-4H3l4 4zm9.06-1.19l-7.87-7.87 1.41-1.41 7.87 7.87-1.41 1.41zM17.5 6c-.32 0-.64.12-.88.37l-4.25 4.25-1.06-1.06-1.41 1.41 1.06 1.06-3.66 3.66 7.06 7.06 6.63-6.63c.48-.48.48-1.27 0-1.76L18.38 6.37A1.24 1.24 0 0017.5 6z"/></svg>
                                        <span class="block h-[3px] w-3.5 rounded-sm -mt-0.5" x-bind:style="`background:${currentHighlightColor}`"></span>
                                    </button>
                                    <template x-teleport="body">
                                        <div
                                            x-show="open" x-cloak x-transition
                                            @click.outside="open = false"
                                            x-bind:style="`position: fixed; top: ${pos.top}px; left: ${pos.left}px; z-index: 9999;`"
                                            class="p-2.5 bg-white border border-[#E0D5C5] rounded-lg shadow-lg flex flex-col"
                                            style="display: none; width: 172px;"
                                        >
                                            <span class="text-[10px] font-semibold text-[#9A8E80] uppercase tracking-wider mb-2 block">Theme Colors</span>
                                            <div class="grid grid-cols-7 gap-1.5 mb-2.5">
                                                <template x-for="c in highlightColors" :key="c">
                                                    <button type="button" @mousedown.prevent="saveSelection()" @click="exec('hiliteColor', c === 'transparent' ? 'transparent' : c); currentHighlightColor = c; open = false" class="w-5 h-5 rounded-full border border-[#E0D5C5] hover:scale-110 transition-transform flex items-center justify-center" x-bind:style="`background:${c}`" :title="c === 'transparent' ? 'No Highlight' : c">
                                                        <template x-if="c === 'transparent'">
                                                            <svg class="w-3 h-3 text-[#E64C4C]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                        </template>
                                                    </button>
                                                </template>
                                            </div>

                                            <div class="h-px bg-[#E8DED2] w-full mb-2.5"></div>

                                            <div class="flex items-center justify-between">
                                                <span class="text-[11.5px] text-[#5A5A5A] font-medium">Custom Color</span>
                                                <div class="relative w-5 h-5 rounded-full border border-[#E0D5C5] overflow-hidden cursor-pointer hover:scale-110 transition-transform shrink-0" title="Pick custom color">
                                                    <input type="color"
                                                           :value="currentHighlightColor === 'transparent' ? '#ffffff' : currentHighlightColor"
                                                           @click.stop="saveSelection()"
                                                           @change="currentHighlightColor = $event.target.value; exec('hiliteColor', $event.target.value); open = false;"
                                                           class="absolute opacity-0 w-full h-full cursor-pointer z-10"
                                                           style="transform: scale(2);"
                                                    >
                                                    <div class="absolute inset-0 pointer-events-none" :style="`background-color: ${currentHighlightColor === 'transparent' ? '#ffffff' : currentHighlightColor}`"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div class="toolbar-divider"></div>

                                <button type="button" @mousedown.prevent="saveSelection()" @click="exec('outdent')" class="toolbar-btn" title="Decrease Indent">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M11 17h10v-2H11v2zm-8-5l4 4V8l-4 4zm0 9h18v-2H3v2zM3 3v2h18V3H3zm8 6h10V7H11v2zm0 4h10v-2H11v2z"/></svg>
                                </button>
                                <button type="button" @mousedown.prevent="saveSelection()" @click="exec('indent')" class="toolbar-btn" title="Increase Indent">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3 21h18v-2H3v2zM3 8v8l4-4-4-4zm8 9h10v-2H11v2zM3 3v2h18V3H3zm8 6h10V7H11v2zm0 4h10v-2H11v2z"/></svg>
                                </button>

                                <div class="toolbar-divider"></div>

                                <button type="button" @mousedown.prevent="saveSelection()" @click="exec('insertHorizontalRule')" class="toolbar-btn" title="Horizontal Rule (--- + Space)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13H5v-2h14v2z"/></svg>
                                </button>
                            </div>

                            {{-- Editor --}}
                            <div class="flex-1 relative overflow-hidden" wire:ignore wire:key="editor-{{ $activeNote->note_id }}">
                                <div
                                    id="note-editor"
                                    contenteditable="true"
                                    class="w-full h-full p-8 text-[15px] text-[#2C2C2C] leading-[1.8] font-['Georgia',serif] overflow-y-auto custom-scrollbar"
                                    style="min-height: 400px; max-height: calc(100vh - 280px);"
                                    data-note-id="{{ $activeNote->note_id }}"
                                    x-init='window.__initialEditorBody = {!! json_encode($activeNote->body ?? '', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}; initEditorElement();'
                                ></div>
                                <div class="absolute bottom-3 right-4 pointer-events-none">
                                    <span id="char-counter" class="text-[11px] text-[#A08866]"></span>
                                </div>
                            </div>

                        @else
                            <div class="flex-1 flex items-center justify-center text-[#A08866] text-[14px]">
                                Select a note to start editing
                            </div>
                        @endif

                    </div>
                </div>
            @endif

            {{-- ============================================================
                 PERBAIKAN: GLOBAL CONTEXT MENU UNTUK TABS
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
                    class="w-44 bg-white border border-[#E0D5C5] rounded-lg shadow-lg py-1 text-[13px] text-[#2C2C2C]"
                    style="display: none;"
                >
                    <button
                        x-show="!menuNoteIsLeaf"
                        class="context-menu-item w-full flex items-center gap-2.5 px-3 py-2 text-left"
                        @click="closeMenu(); $wire.addSubTab(menuNoteId)"
                    >
                        <svg class="w-4 h-4 text-[#8C7558]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Sub-Tab
                    </button>

                    <button
                        class="context-menu-item w-full flex items-center gap-2.5 px-3 py-2 text-left"
                        @click="closeMenu(); startRename(menuNoteId, menuNoteTitle)"
                    >
                        <svg class="w-4 h-4 text-[#8C7558]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Rename
                    </button>

                    <button
                        class="context-menu-item w-full flex items-center gap-2.5 px-3 py-2 text-left"
                        @click="closeMenu(); $wire.duplicateNote(menuNoteId)"
                    >
                        <svg class="w-4 h-4 text-[#8C7558]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        Duplicate
                    </button>

                    <div class="h-px bg-[#E8DED2] my-1"></div>

                    <button
                        class="context-menu-item w-full flex items-center gap-2.5 px-3 py-2 text-left text-[#E64C4C]"
                        @click="
                            closeMenu();
                            if(confirm('Delete \'' + menuNoteTitle + '\'? All sub-tabs will also be deleted.')) {
                                $wire.deleteNote(menuNoteId);
                            }
                        "
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete
                    </button>
                </div>
            </template>

        </div>
    </div>
</div>
