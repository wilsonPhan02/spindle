document.addEventListener('alpine:init', () => {
    Alpine.data('notesManager', () => ({
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
                this.$wire.renameNote(id, this.renameValue);
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
        onDragOver(id, isLeaf, event) {
            if (this.draggedId === id) return;
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            this.dragOverId = id;
            const rect = event.currentTarget.getBoundingClientRect();
            const y = event.clientY - rect.top;
            if (isLeaf) {
                this.dragPos = y < rect.height * 0.5 ? 'before' : 'after';
            } else {
                this.dragPos = y < rect.height * 0.25 ? 'before' : (y > rect.height * 0.75 ? 'after' : 'inside');
            }
        },
        onDrop(targetId, position, event) {
            event.preventDefault();
            if (!this.draggedId || this.draggedId === targetId) {
                this.draggedId = null; this.dragOverId = null; return;
            }
            this.$wire.moveNote(this.draggedId, targetId, position);
            this.draggedId = null;
            this.dragOverId = null;
            this.dragPos = null;
        },
        onDragEnd() {
            this.draggedId = null;
            this.dragOverId = null;
            this.dragPos = null;
        }
    }));
});
