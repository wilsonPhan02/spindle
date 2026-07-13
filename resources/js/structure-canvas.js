/**
 * structure-canvas.js
 *
 * Alpine.js component for the sortable chapter list on the Structure Canvas page.
 *
 * Bug fixes:
 *  1. Re-initializes SortableJS after every Livewire DOM morph so newly-added
 *     chapters are immediately draggable without requiring a page refresh.
 *  2. Binds Sortable to the whole `.sortable-item` wrapper, not just the text
 *     inside, so the user can grab any part of the card to drag it.
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('sortableList', ($wire) => ({
        _sortable: null,

        init() {
            this._initSortable();

            // Re-initialize after every Livewire morph cycle so newly added
            // chapters (which are injected via DOM morphing) are immediately
            // picked up by the Sortable instance.
            this.$el.addEventListener('livewire:morphed', () => {
                this._destroySortable();
                this.$nextTick(() => this._initSortable());
            });
        },

        destroy() {
            this._destroySortable();
        },

        _initSortable() {
            if (!this.$refs.list) return;

            this._sortable = new Sortable(this.$refs.list, {
                animation: 200,
                ghostClass: 'opacity-50',
                dragClass: 'cursor-grabbing',
                chosenClass: 'cursor-grabbing',
                draggable: '.sortable-item',

                // Prevent the native <a> link drag from interfering.
                // SortableJS cancels native drag internally, but being
                // explicit here ensures cross-browser consistency.
                forceFallback: false,

                onEnd: (evt) => {
                    if (evt.oldIndex === evt.newIndex) return;

                    const items = Array.from(
                        this.$refs.list.querySelectorAll('.sortable-item')
                    );

                    const newOrderIds = items.map(item =>
                        item.getAttribute('data-id')
                    );

                    $wire.updateChapterOrder(newOrderIds);
                },
            });
        },

        _destroySortable() {
            if (this._sortable) {
                this._sortable.destroy();
                this._sortable = null;
            }
        },
    }));
});
