<div id="tab-panel" class="w-[220px] shrink-0 border-r border-brand-150 flex flex-col bg-brand-50 relative">

    <div class="flex items-center justify-between px-4 py-3 border-b border-brand-150">
        <span class="text-app-caption font-semibold text-text-60 uppercase tracking-widest">{{ __('Document Tabs') }}</span>
        <button wire:click="addNote" class="w-5 h-5 flex items-center justify-center text-secondary-200 hover:text-secondary-100 transition-colors" title="{{ __('Add new note') }}">
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
            @dragover.stop.prevent="if (draggedId) { dragOverId = '__root__'; dragPos = 'after'; }"
            @dragleave.stop="if (dragOverId === '__root__') dragOverId = null"
            @drop.stop.prevent="if (draggedId) { $wire.moveNote(draggedId, null, 'after'); draggedId = null; dragOverId = null; }"
        ></div>
    </div>

</div>
