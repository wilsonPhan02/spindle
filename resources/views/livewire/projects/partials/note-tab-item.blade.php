{{--
    Partial: note-tab-item.blade.php
    Dirender rekursif untuk hierarki notes hingga 3 level (depth 0, 1, 2).

    Variables:
    - $note: App\Models\Note (dengan relasi childrenRecursive sudah di-load)
    - $activeNoteId: string|null
    - $depth: int (0, 1, atau 2) — level kedalaman saat ini
--}}

@php
    $isActive    = $activeNoteId === $note->note_id;
    $isLeaf      = $note->depth >= \App\Models\Note::MAX_DEPTH; // depth 2 → tidak bisa add sub-tab
    $hasChildren = $note->children->isNotEmpty();
    // Indentasi bertambah jelas tiap level + garis vertikal penunjuk hierarki
    $indentPx    = $depth * 20;
@endphp

<div
    class="relative note-node"
    data-note-id="{{ $note->note_id }}"
    data-depth="{{ $note->depth }}"
    x-bind:class="draggedId === '{{ $note->note_id }}' ? 'dragging-opacity' : ''"
>
    {{-- Garis vertikal penunjuk hierarki (muncul mulai depth 1) --}}
    @if($depth > 0)
        <div class="subtab-line" style="left: {{ ($depth - 1) * 20 + 19 }}px;"></div>
    @endif

    {{-- ============================================================
         Tab Row
    ============================================================ --}}
    <div
        class="tab-item flex items-center gap-1 pr-2 cursor-pointer select-none group relative {{ $isActive ? 'active' : '' }}"
        style="padding-left: {{ 10 + $indentPx }}px; padding-top: 6px; padding-bottom: 6px;"
        wire:click="selectNote('{{ $note->note_id }}')"
        draggable="true"
        @dragstart.stop="onDragStart('{{ $note->note_id }}', $event)"
        @dragend.stop="onDragEnd()"
        @dragover.stop.prevent="onDragOver('{{ $note->note_id }}', {{ $isLeaf ? 'true' : 'false' }}, $event)"
        @dragleave.stop="if (dragOverId === '{{ $note->note_id }}') dragOverId = null"
        @drop.stop.prevent="onDrop('{{ $note->note_id }}', dragPos, $event)"
        x-bind:class="{
            'drag-over-before': dragOverId === '{{ $note->note_id }}' && dragPos === 'before',
            'drag-over-after':  dragOverId === '{{ $note->note_id }}' && dragPos === 'after',
            'drag-over-inside': dragOverId === '{{ $note->note_id }}' && dragPos === 'inside' && {{ $isLeaf ? 'false' : 'true' }}
        }"
    >
        {{-- Drag handle --}}
        <span class="drag-handle shrink-0 text-text-60" title="Drag to move">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor">
                <circle cx="8" cy="6" r="1.5"/><circle cx="16" cy="6" r="1.5"/>
                <circle cx="8" cy="12" r="1.5"/><circle cx="16" cy="12" r="1.5"/>
                <circle cx="8" cy="18" r="1.5"/><circle cx="16" cy="18" r="1.5"/>
            </svg>
        </span>

        {{-- Collapse caret (hanya jika ada children) --}}
        @if($hasChildren)
            <button
                @click.stop="toggleCollapse('{{ $note->note_id }}')"
                class="w-3.5 h-3.5 flex items-center justify-center shrink-0 text-text-60 hover:text-text-90"
            >
                <svg
                    class="collapse-caret"
                    x-bind:class="isCollapsed('{{ $note->note_id }}') ? 'is-collapsed' : ''"
                    width="10" height="10" viewBox="0 0 24 24" fill="currentColor"
                >
                    <path d="M7 10l5 5 5-5z"/>
                </svg>
            </button>
        @else
            <span class="w-3.5 h-3.5 shrink-0"></span>
        @endif

        {{-- File icon --}}
        <svg class="w-3 h-3 shrink-0 {{ $isActive ? 'text-secondary-200' : 'text-text-60' }}"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>

        {{-- Title / Rename input --}}
        <div class="flex-1 min-w-0">
            <span
                x-show="renamingId !== '{{ $note->note_id }}'"
                class="text-app-desc-feature {{ $isActive ? 'text-text-100 font-semibold' : 'text-text-80' }} truncate block"
                title="{{ $note->title }}"
            >{{ $note->title }}</span>
            <div x-show="renamingId === '{{ $note->note_id }}'" class="relative" x-cloak>
                <input
                    id="rename_{{ $note->note_id }}"
                    x-ref="rename_{{ $note->note_id }}"
                    x-model="renameValue"
                    maxlength="25"
                    @keydown.enter="commitRename('{{ $note->note_id }}')"
                    @keydown.escape="renamingId = null"
                    @blur="commitRename('{{ $note->note_id }}')"
                    @click.stop
                    @mousedown.stop
                    class="w-full text-app-desc-feature text-text-100 bg-bg-main border border-brand-200 rounded px-1 py-0.5 outline-none focus:border-secondary-200 pr-8"
                />
                <span class="absolute right-1 top-1/2 -translate-y-1/2 text-app-caption text-text-60" x-text="(renameValue || '').length + '/25'"></span>
            </div>
        </div>

        {{-- PERBAIKAN: Three-dot menu trigger menyalurkan data ke menu Global --}}
        <button
            data-menu-trigger
            data-title="{{ $note->title }}"
            class="w-5 h-5 flex items-center justify-center text-text-60 hover:text-text-90 transition-colors opacity-0 group-hover:opacity-100 shrink-0 rounded hover:bg-brand-150"
            @click.stop="openMenuId === '{{ $note->note_id }}' ? closeMenu() : openMenu('{{ $note->note_id }}', $el.dataset.title, {{ $isLeaf ? 'true' : 'false' }}, $event.currentTarget)"
            title="Options"
        >
            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                <circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/>
            </svg>
        </button>
    </div>

    {{-- ============================================================
         Children (sub-tabs) — collapse-able, rekursif
    ============================================================ --}}
    @if($hasChildren)
        <div x-show="!isCollapsed('{{ $note->note_id }}')" x-cloak>
            @foreach($note->children as $child)
                @include('livewire.projects.partials.note-tab-item', [
                    'note'         => $child,
                    'activeNoteId' => $activeNoteId,
                    'depth'        => $depth + 1,
                ])
            @endforeach
        </div>
    @endif
</div>
