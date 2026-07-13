document.addEventListener('alpine:init', () => {
    Alpine.data('whiteboard', (projectId, characters, relationships, relationshipTypes) => ({
        zoom: 1,
        panX: 0, panY: 0,
        panning: false,
        panStartX: 0, panStartY: 0,
        panOriginX: 0, panOriginY: 0,
        get canvasW() {
            let baseW = 2400 + (Math.floor(this.characters.length / 25) * 800);
            if (this.characters.length > 0) {
                const maxLeft = Math.max(...this.characters.map(c => c.left));
                baseW = Math.max(baseW, maxLeft + 400); // 400px padding
            }
            return baseW;
        },
        get canvasH() {
            let baseH = 1800 + (Math.floor(this.characters.length / 25) * 600);
            if (this.characters.length > 0) {
                const maxTop = Math.max(...this.characters.map(c => c.top));
                baseH = Math.max(baseH, maxTop + 300); // 300px padding
            }
            return baseH;
        },
        projectId,
        characters,
        relationships,
        relationshipTypes,
        infoCharacter: null,
        showCharacterInfoPopup: false,
        searchQuery: '',
        searchType: 'all',
        addingRelation: false,
        relationSourceId: null,
        pendingTargetId: null,
        relPopupOpen: false,
        draggingId: null,
        dragStartX: 0, dragStartY: 0,
        dragOrigTop: 0, dragOrigLeft: 0,
        dragMoved: false,
        draggingLabelRelId: null,
        dragLabelStartX: 0, dragLabelStartY: 0,
        dragLabelOrigOffset: 0,
        labelDragMoved: false,

        init() {
            window.addEventListener('search-query-updated', (e) => {
                const payload = e.detail;
                if (typeof payload === 'string') {
                    this.searchQuery = payload;
                    this.searchType = 'all';
                } else {
                    this.searchQuery = payload.query;
                    this.searchType = payload.type || 'all';
                }
                const matches = {
                    characters: this.searchQuery && (this.searchType === 'all' || this.searchType === 'character') ? this.characters.filter(c => this.matchesCharacterSearch(c)).slice(0, 3) : [],
                    tags: this.searchQuery && (this.searchType === 'all' || this.searchType === 'tag') ? Array.from(this.searchMatchedTags).slice(0, 3) : [],
                    relations: this.searchQuery && (this.searchType === 'all' || this.searchType === 'relation') ? Array.from(new Set(this.relationships.filter(r => this.matchesRelationSearch(r)).map(r => r.name))).slice(0, 3) : []
                };
                window.dispatchEvent(new CustomEvent('search-recommendations', { detail: matches }));

                if (this.searchQuery && this.searchMatchedCharacters.size > 0) {
                    let totalX = 0, totalY = 0;
                    const matchedIds = Array.from(this.searchMatchedCharacters);
                    matchedIds.forEach(id => {
                        const center = this.getCenter(id);
                        totalX += center.x;
                        totalY += center.y;
                    });
                    this.panTo(totalX / matchedIds.length, totalY / matchedIds.length);
                }
            });
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

            this.$watch('characters.length', () => {
                this.$nextTick(() => {
                    this.clampPan();
                });
            });

            this.centerBoard();
        },

        getCenter(id) {
            const c = this.characters.find(c => c.id === id);
            return c ? { x: c.left + 40, y: c.top + 40 } : { x: 0, y: 0 };
        },
        // Kalau ada lebih dari 1 relasi antara pasangan karakter yang sama, tiap
        // relasi dikasih index simetris di sekitar 0 (mis. N=3 -> -1, 0, 1) supaya
        // garisnya bisa di-"bengkokkan" ke sisi yang berbeda dan tidak numpuk.
        getGroupOffsetIndex(rel) {
            const siblings = this.relationships.filter(r =>
                (r.from === rel.from && r.to === rel.to) || (r.from === rel.to && r.to === rel.from)
            );
            if (siblings.length <= 1) return 0;
            const idx = siblings.findIndex(r => r.id === rel.id);
            return idx - (siblings.length - 1) / 2;
        },
        // Kalau user pernah drag label relasi ini, pakai offset manual yang tersimpan.
        // Kalau belum pernah, pakai offset otomatis berdasarkan grouping index.
        getCurveOffset(rel) {
            return (rel.curveOffset !== null && rel.curveOffset !== undefined)
                ? rel.curveOffset
                : this.getGroupOffsetIndex(rel) * 26;
        },
        matchesCharacterSearch(char) {
            if (!this.searchQuery) return false;
            if (this.searchType !== 'all' && this.searchType !== 'character') return false;
            const q = this.searchQuery.toLowerCase();
            if (char.name.toLowerCase().includes(q)) return true;
            return false;
        },
        matchesTagSearch(char) {
            if (!this.searchQuery) return false;
            if (this.searchType !== 'all' && this.searchType !== 'tag') return false;
            const q = this.searchQuery.toLowerCase();
            if (char.tags && char.tags.some(t => t.toLowerCase().includes(q))) return true;
            return false;
        },
        matchesRelationSearch(rel) {
            if (!this.searchQuery) return false;
            if (this.searchType !== 'all' && this.searchType !== 'relation') return false;
            const q = this.searchQuery.toLowerCase();
            if (rel.name.toLowerCase().includes(q)) return true;
            return false;
        },
        get searchMatchedCharacters() {
            if (!this.searchQuery) return new Set();
            return new Set(this.characters.filter(c => this.matchesCharacterSearch(c)).map(c => c.id));
        },
        get searchMatchedRelations() {
            if (!this.searchQuery) return new Set();
            return new Set(this.relationships.filter(r => this.matchesRelationSearch(r)).map(r => r.id));
        },
        get searchMatchedTags() {
            if (!this.searchQuery) return new Set();
            const q = this.searchQuery.toLowerCase();
            const tags = new Set();
            this.characters.forEach(c => {
                if (c.tags) {
                    c.tags.forEach(t => {
                        if (t.toLowerCase().includes(q)) tags.add(t);
                    });
                }
            });
            return tags;
        },
        get hasSearchMatch() {
            if (!this.searchQuery) return false;
            return this.searchMatchedCharacters.size > 0 || this.searchMatchedTags.size > 0 || this.searchMatchedRelations.size > 0;
        },
        isCharacterHighlighted(char) {
            if (!this.hasSearchMatch) return true;
            if (this.searchMatchedCharacters.has(char.id)) return true;
            if (this.matchesTagSearch(char)) return true;
            
            if (this.searchMatchedRelations.size > 0) {
                const connectedToMatchedRel = this.relationships.some(r => 
                    this.searchMatchedRelations.has(r.id) && (r.from === char.id || r.to === char.id)
                );
                if (connectedToMatchedRel) return true;
            }
            return false;
        },
        isRelationHighlighted(rel) {
            if (!this.hasSearchMatch) return true;
            if (this.searchMatchedRelations.has(rel.id)) return true;
            
            if (this.searchMatchedCharacters.size > 0 || this.searchMatchedTags.size > 0) {
                const fromChar = this.characters.find(c => c.id === rel.from);
                const toChar = this.characters.find(c => c.id === rel.to);
                
                if (fromChar && (this.searchMatchedCharacters.has(fromChar.id) || this.matchesTagSearch(fromChar))) return true;
                if (toChar && (this.searchMatchedCharacters.has(toChar.id) || this.matchesTagSearch(toChar))) return true;
            }
            return false;
        },
        /**
         * Calculates the geometric properties of the relationship line connecting two characters.
         * Accounts for character radius and applies a quadratic bezier curve to avoid overlapping 
         * lines when multiple relationships exist between the same two characters.
         * 
         * @param {Object} rel - The relationship object
         * @returns {Object} Line coordinates, control points, and label angle
         */
        relationLine(rel) {
            const from = this.getCenter(rel.from);
            const to = this.getCenter(rel.to);
            const dx = to.x - from.x;
            const dy = to.y - from.y;
            const dist = Math.hypot(dx, dy) || 1;
            const radius = 40;
            const ux = dx / dist, uy = dy / dist;
            const x1 = from.x + ux * radius;
            const y1 = from.y + uy * radius;
            const x2 = to.x - ux * radius;
            const y2 = to.y - uy * radius;
            const angle = Math.atan2(dy, dx);
            // Sudut label di-clamp ke -90..90 derajat, supaya tulisan tetap mengikuti
            // arah garis tapi tidak pernah terbalik/upside-down dari arah manapun.
            let labelAngle = angle;
            if (labelAngle > Math.PI / 2) labelAngle -= Math.PI;
            else if (labelAngle < -Math.PI / 2) labelAngle += Math.PI;

            // Titik kontrol quadratic-bezier digeser tegak lurus terhadap garis A-B
            // sejauh "bend" px (manual kalau pernah di-drag, otomatis kalau belum).
            const bend = this.getCurveOffset(rel);
            const px = -uy, py = ux;
            const mx = (x1 + x2) / 2, my = (y1 + y2) / 2;
            const controlX = mx + px * bend;
            const controlY = my + py * bend;

            // Titik tengah kurva (t=0.5) dipakai buat posisi label. Tangent
            // quadratic-bezier di t=0.5 sejajar dengan garis lurus P0-P2, jadi
            // labelAngle di atas tetap valid tanpa perlu dihitung ulang.
            const midX = 0.25 * x1 + 0.5 * controlX + 0.25 * x2;
            const midY = 0.25 * y1 + 0.5 * controlY + 0.25 * y2;

            return { x1, y1, x2, y2, labelAngle, controlX, controlY, midX, midY };
        },
        /**
         * Generates SVG path markup for all relationships on the whiteboard.
         * Creates two paths for each edge: an invisible wider hit-area for easier clicking,
         * and a visible thinner line styled according to the relationship type.
         * 
         * @returns {string} Combined HTML string of SVG path elements
         */
        edgePathsMarkup() {
            return this.relationships.map(rel => {
                const line = this.relationLine(rel);
                let color = /^#[0-9a-fA-F]{3,8}$/.test(rel.textColor) ? rel.textColor : '#8C7558';
                let opacity = '1';

                if (this.searchQuery && this.hasSearchMatch && !this.isRelationHighlighted(rel)) {
                    color = '#E2E8F0';
                    opacity = '0.3';
                }

                const d = `M ${line.x1} ${line.y1} Q ${line.controlX} ${line.controlY} ${line.x2} ${line.y2}`;
                const hit = `<path d="${d}" stroke="transparent" stroke-width="14" fill="none" pointer-events="stroke" style="cursor:pointer" data-rel-id="${rel.id}"></path>`;
                const visible = `<path d="${d}" stroke="${color}" stroke-width="2" fill="none" pointer-events="none" opacity="${opacity}"></path>`;
                return hit + visible;
            }).join('');
        },
        onEdgeClick(e) {
            const target = e.target.closest('[data-rel-id]');
            if (!target) return;
            const rel = this.relationships.find(r => r.id === target.getAttribute('data-rel-id'));
            if (rel) this.openEditRelation(rel);
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
                char.top = Math.max(40, this.dragOrigTop + dy);
                char.left = Math.max(40, this.dragOrigLeft + dx);
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
        startDragLabel(e, rel) {
            this.draggingLabelRelId = rel.id;
            this.dragLabelStartX = e.clientX;
            this.dragLabelStartY = e.clientY;
            this.dragLabelOrigOffset = this.getCurveOffset(rel);
            this.labelDragMoved = false;
        },
        onDragLabel(e) {
            if (this.draggingLabelRelId === null) return;
            const rel = this.relationships.find(r => r.id === this.draggingLabelRelId);
            if (!rel) return;

            const dx = (e.clientX - this.dragLabelStartX) / this.zoom;
            const dy = (e.clientY - this.dragLabelStartY) / this.zoom;
            if (Math.abs(dx) > 3 || Math.abs(dy) > 3) this.labelDragMoved = true;

            // Proyeksikan pergeseran mouse ke arah tegak lurus garis A-B, supaya
            // gerakan sejajar garis diabaikan dan cuma "bengkokan"-nya yang berubah.
            const from = this.getCenter(rel.from);
            const to = this.getCenter(rel.to);
            const dist = Math.hypot(to.x - from.x, to.y - from.y) || 1;
            const px = -(to.y - from.y) / dist, py = (to.x - from.x) / dist;
            const delta = dx * px + dy * py;

            rel.curveOffset = this.dragLabelOrigOffset + delta;
        },
        stopDragLabel() {
            if (this.draggingLabelRelId !== null && this.labelDragMoved) {
                const rel = this.relationships.find(r => r.id === this.draggingLabelRelId);
                if (rel) {
                    this.$wire.call('updateRelationshipCurve', rel.id, rel.curveOffset);
                }
            }
            this.draggingLabelRelId = null;
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
        relatedTypeIds(idA, idB) {
            return this.relationships
                .filter(r => (r.from === idA && r.to === idB) || (r.from === idB && r.to === idA))
                .map(r => r.typeId);
        },
        selectTarget(id) {
            this.pendingTargetId = id;
            this.addingRelation = false;
            const fromChar = this.characters.find(c => c.id === this.relationSourceId);
            const toChar = this.characters.find(c => c.id === id);
            window.dispatchEvent(new CustomEvent('open-relation-type-popup', { detail: {
                relationId: null,
                charFromName: fromChar?.name ?? null,
                charToName: toChar?.name ?? null,
                usedTypeIds: this.relatedTypeIds(this.relationSourceId, id),
            } }));
        },
        openEditRelation(rel) {
            const fromChar = this.characters.find(c => c.id === rel.from);
            const toChar = this.characters.find(c => c.id === rel.to);
            window.dispatchEvent(new CustomEvent('open-edit-relation-popup', { detail: {
                relationId: rel.id,
                typeId: rel.typeId,
                charFromName: fromChar?.name ?? null,
                charToName: toChar?.name ?? null,
                usedTypeIds: this.relatedTypeIds(rel.from, rel.to),
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
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    this.panX = (this.$el.offsetWidth - this.canvasW * this.zoom) / 2;
                    this.panY = (this.$el.offsetHeight - this.canvasH * this.zoom) / 2;
                    this.clampPan();
                    this.$el.scrollIntoView({ behavior: 'auto', block: 'center' });
                });
            });
        },
        panTo(x, y) {
            requestAnimationFrame(() => {
                const containerW = this.$el.offsetWidth;
                const containerH = this.$el.offsetHeight;
                this.panX = containerW / 2 - x * this.zoom;
                this.panY = containerH / 2 - y * this.zoom;
                this.clampPan();
            });
        },
    }))
})
