document.addEventListener('alpine:init', () => {
    Alpine.data('whiteboard', (projectId, characters, relationships, relationshipTypes) => ({
        zoom: 1,
        panX: 0, panY: 0,
        panning: false,
        panStartX: 0, panStartY: 0,
        panOriginX: 0, panOriginY: 0,
        canvasW: 2400,
        canvasH: 1800,
        projectId,
        characters,
        relationships,
        relationshipTypes,
        infoCharacter: null,
        showCharacterInfoPopup: false,
        addingRelation: false,
        relationSourceId: null,
        pendingTargetId: null,
        relPopupOpen: false,
        draggingId: null,
        dragStartX: 0, dragStartY: 0,
        dragOrigTop: 0, dragOrigLeft: 0,
        dragMoved: false,

        init() {
            window.addEventListener('open-relation-type-popup', () => { this.relPopupOpen = true; });
            window.addEventListener('open-edit-relation-popup', () => { this.relPopupOpen = true; });
            window.addEventListener('relation-popup-closed', () => { this.relPopupOpen = false; });

            window.addEventListener('type-selected', (e) => {
                const { type } = e.detail;
                if (!this.relationshipTypes.find(rt => rt.id === type.id)) {
                    this.relationshipTypes.push(type);
                }
                if (this.relationSourceId && this.pendingTargetId) {
                    this.$wire.call('createRelationship', this.relationSourceId, this.pendingTargetId, type.id).then(newRel => {
                        if (newRel) this.relationships.push(newRel);
                    });
                }
                this.relationSourceId = null;
                this.pendingTargetId = null;
            });

            window.addEventListener('relation-saved', (e) => {
                const { relationId, type } = e.detail;
                const rel = this.relationships.find(r => r.id === relationId);
                if (rel) {
                    rel.typeId = type.id;
                    rel.name = type.name;
                    rel.textColor = type.textColor;
                    rel.bgColor = type.bgColor;
                }
            });

            window.addEventListener('relation-deleted', (e) => {
                const { relationId } = e.detail;
                this.relationships = this.relationships.filter(r => r.id !== relationId);
            });

            window.addEventListener('relation-type-deleted', (e) => {
                const { typeId } = e.detail;
                this.relationshipTypes = this.relationshipTypes.filter(rt => rt.id !== typeId);
                this.relationships = this.relationships.filter(r => r.typeId !== typeId);
            });

            this.centerBoard();
        },

        getCenter(id) {
            const c = this.characters.find(c => c.id === id);
            return c ? { x: c.left + 40, y: c.top + 40 } : { x: 0, y: 0 };
        },
        relationLine(rel) {
            const from = this.getCenter(rel.from);
            const to = this.getCenter(rel.to);
            const dx = to.x - from.x;
            const dy = to.y - from.y;
            const dist = Math.hypot(dx, dy) || 1;
            const radius = 40;
            const x1 = from.x + (dx / dist) * radius;
            const y1 = from.y + (dy / dist) * radius;
            const length = Math.max(0, dist - radius * 2);
            const angle = Math.atan2(dy, dx);
            // Sudut label di-clamp ke -90..90 derajat, supaya tulisan tetap mengikuti
            // arah garis tapi tidak pernah terbalik/upside-down dari arah manapun.
            let labelAngle = angle;
            if (labelAngle > Math.PI / 2) labelAngle -= Math.PI;
            else if (labelAngle < -Math.PI / 2) labelAngle += Math.PI;
            return {
                x1, y1, length, angle, labelAngle,
                midX: x1 + Math.cos(angle) * length / 2,
                midY: y1 + Math.sin(angle) * length / 2,
            };
        },
        startDragChar(e, char) {
            this.draggingId = char.id;
            this.dragStartX = e.clientX;
            this.dragStartY = e.clientY;
            this.dragOrigTop = char.top;
            this.dragOrigLeft = char.left;
            this.dragMoved = false;
        },
        onDragChar(e) {
            if (this.draggingId === null) return;
            const dx = (e.clientX - this.dragStartX) / this.zoom;
            const dy = (e.clientY - this.dragStartY) / this.zoom;
            if (Math.abs(dx) > 3 || Math.abs(dy) > 3) this.dragMoved = true;
            const char = this.characters.find(c => c.id === this.draggingId);
            if (char) {
                char.top = this.dragOrigTop + dy;
                char.left = this.dragOrigLeft + dx;
            }
        },
        stopDragChar() {
            if (this.draggingId !== null && this.dragMoved) {
                const char = this.characters.find(c => c.id === this.draggingId);
                if (char) {
                    this.$wire.call('updateCharacterPosition', char.id, Math.round(char.top), Math.round(char.left));
                }
            }
            this.draggingId = null;
        },
        startAddRelation(id) {
            this.addingRelation = true;
            this.relationSourceId = id;
        },
        cancelAddRelation() {
            this.addingRelation = false;
            this.relationSourceId = null;
        },
        openCharacterInfo(char) {
            this.infoCharacter = char;
            this.showCharacterInfoPopup = true;
        },
        closeCharacterInfo() {
            this.showCharacterInfoPopup = false;
            this.infoCharacter = null;
        },
        viewCharacterDetail() {
            if (!this.infoCharacter) return;
            Livewire.navigate(`/projects/${this.projectId}/characters/${this.infoCharacter.id}`);
        },
        deleteCharacterConfirmed() {
            if (!this.infoCharacter) return;
            const id = this.infoCharacter.id;
            this.characters = this.characters.filter(c => c.id !== id);
            this.relationships = this.relationships.filter(r => r.from !== id && r.to !== id);
            this.$wire.call('deleteCharacter', id);
            this.closeCharacterInfo();
        },
        selectTarget(id) {
            this.pendingTargetId = id;
            this.addingRelation = false;
            window.dispatchEvent(new CustomEvent('open-relation-type-popup', { detail: { relationId: null } }));
        },
        openEditRelation(rel) {
            const fromChar = this.characters.find(c => c.id === rel.from);
            const toChar = this.characters.find(c => c.id === rel.to);
            window.dispatchEvent(new CustomEvent('open-edit-relation-popup', { detail: {
                relationId: rel.id,
                typeId: rel.typeId,
                charFromName: fromChar?.name ?? null,
                charToName: toChar?.name ?? null,
            }}));
        },
        isAnyPopupOpen() {
            return this.relPopupOpen || this.showCharacterInfoPopup;
        },
        clampPan() {
            const containerW = this.$el.offsetWidth;
            const containerH = this.$el.offsetHeight;
            const scaledW = this.canvasW * this.zoom;
            const scaledH = this.canvasH * this.zoom;

            // Kalau kanvas (setelah di-scale) lebih kecil dari kotak whiteboard,
            // izinkan panX/panY positif (sampai sisa selisihnya) supaya kanvas tetap
            // bisa digeser & dicenter, bukan dipaksa nempel ke pojok kiri-atas.
            const minPanX = Math.min(0, containerW - scaledW);
            const maxPanX = Math.max(0, containerW - scaledW);
            const minPanY = Math.min(0, containerH - scaledH);
            const maxPanY = Math.max(0, containerH - scaledH);

            this.panX = Math.min(maxPanX, Math.max(minPanX, this.panX));
            this.panY = Math.min(maxPanY, Math.max(minPanY, this.panY));
        },
        applyZoom(newZoom) {
            // Jaga titik yang sedang terlihat di tengah viewport supaya tetap di tengah
            // setelah zoom berubah (bukan ikut bergeser ke arah pojok kiri-atas kanvas).
            const containerW = this.$el.offsetWidth;
            const containerH = this.$el.offsetHeight;
            const centerCanvasX = (containerW / 2 - this.panX) / this.zoom;
            const centerCanvasY = (containerH / 2 - this.panY) / this.zoom;

            this.zoom = newZoom;
            this.panX = containerW / 2 - centerCanvasX * newZoom;
            this.panY = containerH / 2 - centerCanvasY * newZoom;
            this.clampPan();
        },
        zoomIn() {
            this.applyZoom(Math.min(2, +(this.zoom + 0.1).toFixed(2)));
        },
        zoomOut() {
            this.applyZoom(Math.max(0.5, +(this.zoom - 0.1).toFixed(2)));
        },
        onWheel(e) {
            if (this.isAnyPopupOpen()) return;
            const delta = e.deltaY > 0 ? -0.1 : 0.1;
            this.applyZoom(Math.min(2, Math.max(0.5, +(this.zoom + delta).toFixed(2))));
        },
        startPan(e) {
            if (this.isAnyPopupOpen()) return;
            this.panning = true;
            this.panStartX = e.clientX;
            this.panStartY = e.clientY;
            this.panOriginX = this.panX;
            this.panOriginY = this.panY;
        },
        onPan(e) {
            if (this.draggingId !== null) return;
            if (!this.panning) return;
            this.panX = this.panOriginX + (e.clientX - this.panStartX);
            this.panY = this.panOriginY + (e.clientY - this.panStartY);
            this.clampPan();
        },
        stopPan() { this.panning = false; },
        centerBoard() {
            // Pakai double requestAnimationFrame, supaya ukuran elemen (offsetWidth/Height)
            // sudah benar-benar final setelah layout/navigasi selesai sebelum dihitung.
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    this.panX = (this.$el.offsetWidth - this.canvasW * this.zoom) / 2;
                    this.panY = (this.$el.offsetHeight - this.canvasH * this.zoom) / 2;
                    this.clampPan();
                    this.$el.scrollIntoView({ behavior: 'auto', block: 'center' });
                });
            });
        },
    }))
})
