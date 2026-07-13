@props([
    'editorId' => 'text-editor-' . uniqid(),
    'contentProp' => 'editorBody',
    'updateMethod' => 'updateContent',
    'counterType' => 'word',
    'showLists' => true,
    'showColors' => true,
    'showIndent' => true,
    'showHr' => true,
    'showStrike' => true,
    'showTodo' => true,
    'showHighlight' => true,
])

@php
    $customColorPath = resource_path('views/components/custom-color.blade.php');
    $customColorsList = file_exists($customColorPath) ? json_decode(file_get_contents($customColorPath), true) : [];
    $presetTextColors = !empty($customColorsList) ? array_merge(['#000000'], array_column($customColorsList, 'textColor')) : ['#000000', '#43A047', '#1E88E5', '#D81B60', '#8E24AA', '#FB8C00', '#E53935', '#00897B', '#F9A825', '#3949AB', '#00ACC1', '#C0CA33', '#F4511E'];
    $presetBgColors = !empty($customColorsList) ? array_merge(['transparent'], array_column($customColorsList, 'bgColor')) : ['transparent', '#C8E6C9', '#BBDEFB', '#F8BBD0', '#E1BEE7', '#FFE0B2', '#FFCDD2', '#B2DFDB', '#FFF9C4', '#C5CAE9', '#B2EBF2', '#F0F4C3', '#FFCCBC'];
    $textColorsJs = '[' . implode(', ', array_map(fn($c) => "'" . $c . "'", $presetTextColors)) . ']';
    $bgColorsJs = '[' . implode(', ', array_map(fn($c) => "'" . $c . "'", $presetBgColors)) . ']';
@endphp
<div
    {{ $attributes->merge(['class' => 'flex-1 flex flex-col min-w-0 min-h-0 relative bg-brand-50']) }}
    x-data="textEditor({
        editorId: '{{ $editorId }}',
        contentProp: '{{ $contentProp }}',
        updateMethod: '{{ $updateMethod }}',
        textColors: {!! $textColorsJs !!},
        highlightColors: {!! $bgColorsJs !!},
        showLists: {{ $showLists ? 'true' : 'false' }},
        showColors: {{ $showColors ? 'true' : 'false' }},
        showIndent: {{ $showIndent ? 'true' : 'false' }},
        showHr: {{ $showHr ? 'true' : 'false' }},
        showStrike: {{ $showStrike ? 'true' : 'false' }},
        showTodo: {{ $showTodo ? 'true' : 'false' }},
        showHighlight: {{ $showHighlight ? 'true' : 'false' }},
        i18n: {
            normalText: '{{ __('Normal Text') }}',
            heading1: '{{ __('Heading 1') }}',
            heading2: '{{ __('Heading 2') }}',
            heading3: '{{ __('Heading 3') }}',
            quote: '{{ __('Quote') }}'
        }
    })"
>
    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: var(--color-secondary-100); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: var(--color-secondary-150); }

        #{{ $editorId }}:focus { outline: none; }
        #{{ $editorId }} p { margin-bottom: 1em; }
        #{{ $editorId }} h1 { font-size: 2em; font-weight: 700 !important; margin-bottom: 0.5em; }
        #{{ $editorId }} h2 { font-size: 1.5em; font-weight: 600 !important; margin-bottom: 0.5em; }
        #{{ $editorId }} h3 { font-size: 1.17em; font-weight: 600 !important; margin-bottom: 0.5em; }
        #{{ $editorId }} h1, #{{ $editorId }} h1 * { font-weight: 700 !important; font-size: 2em !important; line-height: 1.3 !important; color: var(--color-text-90) !important; }
        #{{ $editorId }} h2, #{{ $editorId }} h2 * { font-weight: 600 !important; font-size: 1.5em !important; line-height: 1.35 !important; color: var(--color-text-90) !important; }
        #{{ $editorId }} h3, #{{ $editorId }} h3 * { font-weight: 600 !important; font-size: 1.17em !important; line-height: 1.4 !important; color: var(--color-text-90) !important; }
        
        #{{ $editorId }} ul { list-style-type: disc; padding-left: 1.5em; }
        #{{ $editorId }} ul ul { list-style-type: circle; }
        #{{ $editorId }} ul ul ul { list-style-type: square; }
        #{{ $editorId }} ul ul ul ul { list-style-type: disc; }
        #{{ $editorId }} ul ul ul ul ul { list-style-type: circle; }
        #{{ $editorId }} ul ul ul ul ul ul { list-style-type: square; }

        #{{ $editorId }} ol { list-style: decimal; padding-left: 1.5em; }
        #{{ $editorId }} hr { border: none; border-top: 1px solid var(--color-brand-200); margin: 1em 0; }
        #{{ $editorId }} blockquote { border-left: 3px solid var(--color-brand-200); padding-left: 1em; color: var(--color-text-70); }
        #{{ $editorId }} .todo-item { margin-top: 0.25em; margin-bottom: 0.25em; line-height: 1.5; }

        .toolbar-scroll::-webkit-scrollbar { display: none; }
        .toolbar-scroll { -ms-overflow-style: none; scrollbar-width: none; cursor: default; }
        
        /* Persis seperti di notes.blade.php */
        .toolbar-btn {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            width: 28px; height: 28px; border-radius: 5px;
            cursor: pointer; color: var(--color-text-70); flex-shrink: 0;
            transition: background-color 0.1s, color 0.1s;
            border: 1px solid transparent;
        }
        .toolbar-btn:hover { background-color: var(--color-brand-150); }
        .toolbar-btn.is-active {
            background-color: var(--color-secondary-20);
            color: var(--color-secondary-200);
            border-color: var(--color-secondary-100);
        }
        .toolbar-divider { width: 1px; height: 18px; background-color: var(--color-brand-200); flex-shrink: 0; margin: 0 2px; }
        .format-option.bg-brand-150 { background-color: var(--color-brand-150); }
    </style>

    {{-- Toolbar Wrapper with Gradient Mask for Overflow --}}
    <div class="relative shrink-0 border-b border-brand-200 bg-brand-100 select-none overflow-hidden"
         x-data="{
             showLeftFade: false,
             showRightFade: false,
             checkScroll() {
                 const el = $refs.toolbarScroll;
                 if (!el) return;
                 this.showLeftFade = el.scrollLeft > 4;
                 this.showRightFade = el.scrollWidth - (el.scrollLeft + el.clientWidth) > 4;
             }
         }"
         x-init="$nextTick(() => { checkScroll(); if (window.ResizeObserver) { new ResizeObserver(() => checkScroll()).observe($refs.toolbarScroll); } });"
    >
        {{-- Left Gradient Mask + Clickable Arrow --}}
        <div x-show="showLeftFade"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute left-0 top-0 bottom-0 w-14 bg-gradient-to-r from-brand-100 via-brand-100/90 to-transparent pointer-events-none z-10 flex items-center justify-start pl-2"
             style="display: none;"
        >
            <button
                type="button"
                @mousedown.prevent="saveSelection()"
                @click="$refs.toolbarScroll.scrollBy({ left: -150, behavior: 'smooth' });"
                class="p-1 text-secondary-200 hover:text-text-90 transition-colors duration-150 pointer-events-auto cursor-pointer outline-none"
                title="{{ __('Scroll Left') }}"
            >
                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M18 4L6 12l12 8V4z"/></svg>
            </button>
        </div>

        {{-- Scrollable Toolbar Area --}}
        <div x-ref="toolbarScroll"
             @scroll.throttle.20ms="checkScroll()"
             @wheel.prevent="$el.scrollLeft += ($event.deltaY || $event.deltaX); checkScroll();"
             class="flex items-center px-3 py-2 gap-1 toolbar-scroll overflow-x-auto"
        >
        
        {{-- Format Dropdown --}}
        <div class="relative shrink-0" x-data="{ pos: { top: 0, left: 0 } }" @mouseenter="onToolbarAreaEnter('format')" @mouseleave="onToolbarAreaLeave('format')">
            <button
                type="button"
                @mousedown.prevent="saveSelection()"
                @click.stop="
                    const r = $event.currentTarget.getBoundingClientRect();
                    pos = { top: r.bottom + 6, left: r.left };
                    activeDropdown = activeDropdown === 'format' ? null : 'format';
                    if (activeDropdown === 'format') adjustOverlayPosition(pos, 180, 220, $event.currentTarget);
                "
                class="h-7 px-2.5 flex items-center gap-1.5 text-app-desc-feature text-text-80 bg-card-bg border border-brand-200 rounded-md outline-none cursor-pointer hover:border-secondary-100 transition-colors shadow-sm"
                style="min-width: 118px;"
                title="{{ __('Text Format') }}"
            >
                <span x-text="formatLabel" class="flex-1 text-left"></span>
                <svg class="w-3 h-3 text-secondary-150 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <template x-teleport="body">
                <div
                    x-show="activeDropdown === 'format'"
                    @mouseenter="onToolbarAreaEnter('format')"
                    @mouseleave="onToolbarAreaLeave('format')"
                    x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    @click.outside="activeDropdown = null"
                    x-bind:style="`position: fixed; top: ${pos.top}px; left: ${pos.left}px; z-index: 9999;`"
                    class="w-40 bg-brand-10 border border-brand-200 rounded-lg py-1"
                    style="display: none;"
                >
                    <button type="button" @mousedown.prevent @click="setFormat('p', '{{ __('Normal Text') }}'); activeDropdown = null" class="format-option w-full text-left px-3 py-1.5 text-app-body-medium text-text-90 hover:bg-brand-50" x-bind:class="formatValue === 'p' ? 'bg-brand-150 font-semibold' : ''" title="{{ __('Normal Text') }}">{{ __('Normal Text') }}</button>
                    <button type="button" @mousedown.prevent @click="setFormat('h1', '{{ __('Heading 1') }}'); activeDropdown = null" class="format-option w-full text-left px-3 py-1.5 text-app-subtitle-1 font-bold text-text-90 hover:bg-brand-50" x-bind:class="formatValue === 'h1' ? 'bg-brand-150' : ''" title="{{ __('Heading 1 (# + Space)') }}">{{ __('Heading 1') }}</button>
                    <button type="button" @mousedown.prevent @click="setFormat('h2', '{{ __('Heading 2') }}'); activeDropdown = null" class="format-option w-full text-left px-3 py-1.5 text-app-subfeature font-bold text-text-90 hover:bg-brand-50" x-bind:class="formatValue === 'h2' ? 'bg-brand-150' : ''" title="{{ __('Heading 2 (## + Space)') }}">{{ __('Heading 2') }}</button>
                    <button type="button" @mousedown.prevent @click="setFormat('h3', '{{ __('Heading 3') }}'); activeDropdown = null" class="format-option w-full text-left px-3 py-1.5 text-app-body-medium font-bold text-text-90 hover:bg-brand-50" x-bind:class="formatValue === 'h3' ? 'bg-brand-150' : ''" title="{{ __('Heading 3 (### + Space)') }}">{{ __('Heading 3') }}</button>
                    <button type="button" @mousedown.prevent @click="setFormat('blockquote', '{{ __('Quote') }}'); activeDropdown = null" class="format-option w-full text-left px-3 py-1.5 text-app-body-medium italic text-text-70 hover:bg-brand-50" x-bind:class="formatValue === 'blockquote' ? 'bg-brand-150' : ''" title="{{ __('Quote (> + Space)') }}">{{ __('Quote') }}</button>
                </div>
            </template>
        </div>

        <div class="toolbar-divider"></div>

        <button type="button" @mousedown.prevent="saveSelection()" @click="exec('bold')" class="toolbar-btn" x-bind:class="activeStates.bold ? 'is-active' : ''" title="{{ __('Bold (Ctrl+B or **text**)') }}">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M15.6 10.79c.97-.67 1.65-1.77 1.65-2.79 0-2.26-1.75-4-4-4H7v14h7.04c2.09 0 3.71-1.7 3.71-3.79 0-1.52-.86-2.82-2.15-3.42zM10 6.5h3c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-3v-3zm3.5 9H10v-3h3.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5z"/></svg>
        </button>
        <button type="button" @mousedown.prevent="saveSelection()" @click="exec('italic')" class="toolbar-btn" x-bind:class="activeStates.italic ? 'is-active' : ''" title="{{ __('Italic (Ctrl+I or *text*)') }}">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M10 4v3h2.21l-3.42 8H6v3h8v-3h-2.21l3.42-8H18V4z"/></svg>
        </button>
        <button type="button" @mousedown.prevent="saveSelection()" @click="exec('underline')" class="toolbar-btn" x-bind:class="activeStates.underline ? 'is-active' : ''" title="{{ __('Underline (Ctrl+U)') }}">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17c3.31 0 6-2.69 6-6V3h-2.5v8c0 1.93-1.57 3.5-3.5 3.5S8.5 12.93 8.5 11V3H6v8c0 3.31 2.69 6 6 6zm-7 2v2h14v-2H5z"/></svg>
        </button>
        
        @if($showStrike)
            <button type="button" @mousedown.prevent="saveSelection()" @click="exec('strikeThrough')" class="toolbar-btn" x-bind:class="activeStates.strikeThrough ? 'is-active' : ''" title="{{ __('Strikethrough (~~text~~)') }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M10 19h4v-3h-4v3zM5 4v3h5v3h4V7h5V4H5zM3 14h18v-2H3v2z"/></svg>
            </button>
        @endif

        @if($showLists || $showTodo)
            <div class="toolbar-divider"></div>
        @endif

        @if($showLists)
            <button type="button" @mousedown.prevent="saveSelection()" @click="exec('insertUnorderedList')" class="toolbar-btn" x-bind:class="activeStates.insertUnorderedList ? 'is-active' : ''" title="{{ __('Bullet List (-, *, or + and Space)') }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M4 10.5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm0-6c-.83 0-1.5.67-1.5 1.5S3.17 7.5 4 7.5 5.5 6.83 5.5 6 4.83 4.5 4 4.5zm0 12c-.83 0-1.5.68-1.5 1.5s.68 1.5 1.5 1.5 1.5-.68 1.5-1.5-.67-1.5-1.5-1.5zM7 19h14v-2H7v2zm0-6h14v-2H7v2zm0-8v2h14V5H7z"/></svg>
            </button>
            <button type="button" @mousedown.prevent="saveSelection()" @click="exec('insertOrderedList')" class="toolbar-btn" x-bind:class="activeStates.insertOrderedList ? 'is-active' : ''" title="{{ __('Numbered List (1. or a. and Space)') }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M2 17h2v.5H3v1h1v.5H2v1h3v-4H2v1zm1-9h1V4H2v1h1v3zm-1 3h1.8L2 13.1v.9h3v-1H3.2L5 10.9V10H2v1zm5-6v2h14V5H7zm0 14h14v-2H7v2zm0-6h14v-2H7v2z"/></svg>
            </button>
        @endif

        @if($showTodo)
            <button type="button" @mousedown.prevent="saveSelection()" @click="restoreSelection(); insertTodoBlock()" class="toolbar-btn" x-bind:class="activeStates.todo ? 'is-active' : ''" title="{{ __('To-Do List ([] + Space)') }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </button>
        @endif

        <div class="toolbar-divider"></div>

        <button type="button" @mousedown.prevent="saveSelection()" @click="exec('justifyLeft')" class="toolbar-btn" x-bind:class="activeStates.justifyLeft ? 'is-active' : ''" title="{{ __('Align Left') }}">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M15 15H3v2h12v-2zm0-8H3v2h12V7zM3 13h18v-2H3v2zm0 8h18v-2H3v2zM3 3v2h18V3H3z"/></svg>
        </button>
        <button type="button" @mousedown.prevent="saveSelection()" @click="exec('justifyCenter')" class="toolbar-btn" x-bind:class="activeStates.justifyCenter ? 'is-active' : ''" title="{{ __('Align Center') }}">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M7 15v2h10v-2H7zm-4 6h18v-2H3v2zm0-8h18v-2H3v2zm4-6v2h10V7H7zM3 3v2h18V3H3z"/></svg>
        </button>
        <button type="button" @mousedown.prevent="saveSelection()" @click="exec('justifyRight')" class="toolbar-btn" x-bind:class="activeStates.justifyRight ? 'is-active' : ''" title="{{ __('Align Right') }}">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3 21h18v-2H3v2zm6-4h12v-2H9v2zm-6-4h18v-2H3v2zm6-4h12V7H9v2zM3 3v2h18V3H3z"/></svg>
        </button>
        <button type="button" @mousedown.prevent="saveSelection()" @click="exec('justifyFull')" class="toolbar-btn" x-bind:class="activeStates.justifyFull ? 'is-active' : ''" title="{{ __('Justify') }}">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3 21h18v-2H3v2zm0-4h18v-2H3v2zm0-4h18v-2H3v2zm0-4h18V7H3v2zm0-6v2h18V3H3z"/></svg>
        </button>

        @if($showColors)
            <div class="toolbar-divider"></div>

            {{-- Text Color with Preset Color Picker --}}
            <div class="relative" x-data="{ pos: { top: 0, left: 0 } }">
                <button
                    type="button"
                    @mousedown.prevent="saveSelection()"
                    @click.stop="
                        const r = $event.currentTarget.getBoundingClientRect();
                        pos = { top: r.bottom + 6, left: r.left };
                        activeDropdown = activeDropdown === 'textColor' ? null : 'textColor';
                        if (activeDropdown === 'textColor') adjustOverlayPosition(pos, 172, 140, $event.currentTarget);
                    "
                    class="toolbar-btn" title="Text Color"
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M11 3L5.5 17h2.25l1.12-3h6.25l1.12 3h2.25L13 3h-2zm-1.38 9L12 5.67 14.38 12H9.62z"/></svg>
                    <span class="block h-[3px] w-3.5 rounded-sm -mt-0.5" x-bind:style="`background:${currentTextColor || 'var(--color-text-80)'}`"></span>
                </button>
                <template x-teleport="body">
                    <div
                        x-show="activeDropdown === 'textColor'" x-cloak x-transition
                        @click.outside="activeDropdown = null"
                        x-bind:style="`position: fixed; top: ${pos.top}px; left: ${pos.left}px; z-index: 9999;`"
                        class="p-2.5 bg-white border border-brand-200 rounded-lg shadow-lg flex flex-col"
                        style="display: none; width: 172px;"
                    >
                        <span class="text-app-caption font-semibold text-secondary-150 uppercase tracking-wider mb-2 block">{{ __('Text Color') }}</span>
                        <div class="grid grid-cols-6 gap-2">
                            <template x-for="c in textColors" :key="c">
                                <button type="button" @mousedown.prevent="saveSelection()" @click="exec('foreColor', c); currentTextColor = c;" class="w-5 h-5 rounded-full transition-all duration-150 relative flex items-center justify-center shrink-0" x-bind:class="(currentTextColor || '#000000') === c ? 'border border-secondary-200 bg-white p-[3px]' : 'border border-brand-200 hover:scale-110 hover:border-secondary-150'" x-bind:style="(currentTextColor || '#000000') === c ? '' : `background:${c}`" :title="c === '#000000' ? '{{ __('Default / Black') }}' : c">
                                    <template x-if="(currentTextColor || '#000000') === c">
                                        <span class="w-full h-full rounded-full block" x-bind:style="`background:${c}`"></span>
                                    </template>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        @endif

        @if($showHighlight)
            {{-- Highlight Color with Preset Color Picker --}}
            <div class="relative" x-data="{ pos: { top: 0, left: 0 } }">
                <button
                    type="button"
                    @mousedown.prevent="saveSelection()"
                    @click.stop="
                        const r = $event.currentTarget.getBoundingClientRect();
                        pos = { top: r.bottom + 6, left: r.left };
                        activeDropdown = activeDropdown === 'highlightColor' ? null : 'highlightColor';
                        if (activeDropdown === 'highlightColor') adjustOverlayPosition(pos, 184, 140, $event.currentTarget);
                    "
                    class="toolbar-btn" title="Highlight (==text==)"
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M7 22l4-4H3l4 4zm9.06-1.19l-7.87-7.87 1.41-1.41 7.87 7.87-1.41 1.41zM17.5 6c-.32 0-.64.12-.88.37l-4.25 4.25-1.06-1.06-1.41 1.41 1.06 1.06-3.66 3.66 7.06 7.06 6.63-6.63c.48-.48.48-1.27 0-1.76L18.38 6.37A1.24 1.24 0 0017.5 6z"/></svg>
                    <span class="block h-[3px] w-3.5 rounded-sm -mt-0.5" x-bind:style="`background:${currentHighlightColor || 'transparent'}`"></span>
                </button>
                <template x-teleport="body">
                    <div
                        x-show="activeDropdown === 'highlightColor'" x-cloak x-transition
                        @click.outside="activeDropdown = null"
                        x-bind:style="`position: fixed; top: ${pos.top}px; left: ${pos.left}px; z-index: 9999;`"
                        class="p-2.5 bg-white border border-brand-200 rounded-lg shadow-lg flex flex-col"
                        style="display: none; width: 184px;"
                    >
                        <span class="text-app-caption font-semibold text-secondary-150 uppercase tracking-wider mb-2 block">{{ __('Highlight Color') }}</span>
                        <div class="grid grid-cols-7 gap-1.5">
                            <template x-for="c in highlightColors" :key="c">
                                <button type="button" @mousedown.prevent="saveSelection()" @click="exec('hiliteColor', c === 'transparent' ? 'transparent' : c); currentHighlightColor = c;" class="w-5 h-5 rounded-full transition-all duration-150 relative flex items-center justify-center shrink-0" x-bind:class="(currentHighlightColor || 'transparent') === c ? 'border border-secondary-200 bg-white p-[3px]' : 'border border-brand-200 hover:scale-110 hover:border-secondary-150'" x-bind:style="(currentHighlightColor || 'transparent') === c || c === 'transparent' ? '' : `background:${c}`" :title="c === 'transparent' ? '{{ __('No Highlight') }}' : c">
                                    <template x-if="c === 'transparent'">
                                        <svg class="w-full h-full text-danger-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </template>
                                    <template x-if="c !== 'transparent' && (currentHighlightColor || 'transparent') === c">
                                        <span class="w-full h-full rounded-full block" x-bind:style="`background:${c}`"></span>
                                    </template>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        @endif

        @if($showIndent || $showHr)
            <div class="toolbar-divider"></div>
        @endif

        @if($showIndent)
            <button type="button" @mousedown.prevent="saveSelection()" @click="customIndent(true)" class="toolbar-btn" title="{{ __('Decrease Indent') }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M11 17h10v-2H11v2zm-8-5l4 4V8l-4 4zm0 9h18v-2H3v2zM3 3v2h18V3H3zm8 6h10V7H11v2zm0 4h10v-2H11v2z"/></svg>
            </button>
            <button type="button" @mousedown.prevent="saveSelection()" @click="customIndent(false)" class="toolbar-btn" title="{{ __('Increase Indent') }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3 21h18v-2H3v2zM3 8v8l4-4-4-4zm8 9h10v-2H11v2zM3 3v2h18V3H3zm8 6h10V7H11v2zm0 4h10v-2H11v2z"/></svg>
            </button>
        @endif

        @if($showHr)
            <button type="button" @mousedown.prevent="saveSelection()" @click="exec('insertHorizontalRule')" class="toolbar-btn" title="{{ __('Horizontal Rule (--- + Space)') }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13H5v-2h14v2z"/></svg>
            </button>
        @endif
        </div>

        {{-- Right Gradient Mask + Clickable Arrow --}}
        <div x-show="showRightFade"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute right-0 top-0 bottom-0 w-14 bg-gradient-to-l from-brand-100 via-brand-100/90 to-transparent pointer-events-none z-10 flex items-center justify-end pr-2"
             style="display: none;"
        >
            <button
                type="button"
                @mousedown.prevent="saveSelection()"
                @click="$refs.toolbarScroll.scrollBy({ left: 150, behavior: 'smooth' });"
                class="p-1 text-secondary-200 hover:text-text-90 transition-colors duration-150 pointer-events-auto cursor-pointer outline-none"
                title="{{ __('Scroll Right') }}"
            >
                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M6 4l12 8-12 8V4z"/></svg>
            </button>
        </div>
    </div>

    {{-- Editor Content Area (bg-brand-50) --}}
    <div class="flex-1 relative overflow-hidden flex flex-col bg-brand-50"
         wire:ignore
         wire:key="editor-container-{{ $editorId }}"
         @mousemove.throttle.50ms="onEditorMouseMove($event)"
         @mouseleave="if (!isDraggingBlock) { blockDragHandle.show = false; }"
         @dragover.prevent.throttle.50ms="onEditorDragOver($event)"
         @drop="onEditorDrop($event)"
    >
        <div
            id="{{ $editorId }}"
            contenteditable="true"
            class="w-full h-full flex-1 p-8 lg:p-10 text-app-body-large text-text-90 leading-[1.65] overflow-y-auto custom-scrollbar bg-brand-50"
        ></div>

        {{-- Floating Block Drag Handle --}}
        <div x-show="blockDragHandle.show"
             class="absolute flex items-center justify-center cursor-grab text-secondary-100 hover:text-secondary-200 transition-colors select-none"
             :style="`top: ${blockDragHandle.top}px; left: ${blockDragHandle.left}px; width: 24px; height: 24px; z-index: 50;`"
             draggable="true"
             @dragstart="onBlockDragStart($event)"
             @dragend="onBlockDragEnd($event)"
             @mouseenter="blockDragHandle.show = true"
             title="{{ __('Drag to move block') }}"
             style="display: none;"
        >
            <svg viewBox="0 0 24 24" fill="currentColor" class="w-[16px] h-[16px]">
                <circle cx="8" cy="6" r="1.5"></circle>
                <circle cx="14" cy="6" r="1.5"></circle>
                <circle cx="8" cy="12" r="1.5"></circle>
                <circle cx="14" cy="12" r="1.5"></circle>
                <circle cx="8" cy="18" r="1.5"></circle>
                <circle cx="14" cy="18" r="1.5"></circle>
            </svg>
        </div>

        {{-- Drop Indicator Line --}}
        <div x-show="dragIndicator.show"
             class="absolute left-8 right-8 h-[2px] bg-secondary-200 pointer-events-none transition-all duration-75"
             :style="`top: ${dragIndicator.top}px; z-index: 60;`"
             style="display: none;"
        >
            <div class="absolute -left-1 -top-[3px] w-2 h-2 rounded-full bg-secondary-200"></div>
            <div class="absolute -right-1 -top-[3px] w-2 h-2 rounded-full bg-secondary-200"></div>
        </div>
    </div>

    {{-- Counter Footer inside Editor Sheet --}}
    <div class="px-8 lg:px-10 py-2.5 text-app-desc-feature text-secondary-150 font-medium border-t border-transparent select-none shrink-0 bg-brand-50 right-8">
        @if(in_array(strtolower($counterType), ['notes', 'limit', 'char-limit', 'char_limit', 'len']))
            <span x-text="new Intl.NumberFormat().format(charCount) + ' / 65,535 • ' + new Intl.NumberFormat().format(wordCount) + ' {{ __('words') }}'"></span>
        @elseif(in_array(strtolower($counterType), ['character', 'char', 'characters', 'karakter']))
            <span x-text="new Intl.NumberFormat().format(charCount) + ' {{ __('characters') }}'"></span>
        @elseif(in_array(strtolower($counterType), ['both', 'all']))
            <span x-text="new Intl.NumberFormat().format(wordCount) + ' {{ __('words') }} · ' + new Intl.NumberFormat().format(charCount) + ' {{ __('characters') }}'"></span>
        @else
            <span x-text="new Intl.NumberFormat().format(wordCount) + ' {{ __('words') }}'"></span>
        @endif
    </div>
</div>