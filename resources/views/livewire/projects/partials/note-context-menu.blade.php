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
        class="w-48 bg-card-bg border border-brand-150 rounded-lg shadow-lg py-1 overflow-hidden z-50"
        style="display: none;"
    >
        <button
            x-show="!menuNoteIsLeaf"
            class="w-full text-left px-4 py-2 text-app-body-medium text-text-80 hover:bg-brand-10 flex items-center gap-3 transition-colors"
            @click="closeMenu(); $wire.addSubTab(menuNoteId)"
        >
            <x-icons.add class="w-4 h-4 shrink-0 text-text-80" />
            {{ __('Add Sub-Tab') }}
        </button>

        <button
            class="w-full text-left px-4 py-2 text-app-body-medium text-text-80 hover:bg-brand-10 flex items-center gap-3 transition-colors"
            @click="closeMenu(); startRename(menuNoteId, menuNoteTitle)"
        >
            <x-icons.rename class="w-4 h-4 shrink-0 text-text-80" />
            {{ __('Rename') }}
        </button>

        <button
            class="w-full text-left px-4 py-2 text-app-body-medium text-text-80 hover:bg-brand-10 flex items-center gap-3 transition-colors"
            @click="closeMenu(); $wire.duplicateNote(menuNoteId)"
        >
            <x-icons.duplicate class="w-4 h-4 shrink-0 text-text-80" />
            {{ __('Duplicate') }}
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
            {{ __('Delete') }}
        </button>
    </div>
</template>
