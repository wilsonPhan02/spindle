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
    $customColorPath = resource_path('views/components/custom_color.blade.php');
    if (!file_exists($customColorPath)) {
        $customColorPath = resource_path('views/components/custom-color.blade.php');
    }
    $customColorsList = file_exists($customColorPath) ? json_decode(file_get_contents($customColorPath), true) : [];
    $presetTextColors = !empty($customColorsList) ? array_merge(['#000000'], array_column($customColorsList, 'textColor')) : ['#000000', '#43A047', '#1E88E5', '#D81B60', '#8E24AA', '#FB8C00', '#E53935', '#00897B', '#F9A825', '#3949AB', '#00ACC1', '#C0CA33', '#F4511E'];
    $presetBgColors = !empty($customColorsList) ? array_merge(['transparent'], array_column($customColorsList, 'bgColor')) : ['transparent', '#C8E6C9', '#BBDEFB', '#F8BBD0', '#E1BEE7', '#FFE0B2', '#FFCDD2', '#B2DFDB', '#FFF9C4', '#C5CAE9', '#B2EBF2', '#F0F4C3', '#FFCCBC'];
    $textColorsJs = '[' . implode(', ', array_map(fn($c) => "'" . $c . "'", $presetTextColors)) . ']';
    $bgColorsJs = '[' . implode(', ', array_map(fn($c) => "'" . $c . "'", $presetBgColors)) . ']';
@endphp
<div
    {{ $attributes->merge(['class' => 'flex-1 flex flex-col min-w-0 min-h-0 relative bg-brand-50']) }}
    x-data="{
        formatValue: 'p',
        formatLabel: 'Normal Text',
        activeStates: {
            bold: false, italic: false, underline: false, strikeThrough: false,
            insertUnorderedList: false, insertOrderedList: false, todo: false,
            justifyLeft: true, justifyCenter: false, justifyRight: false, justifyFull: false,
        },
        activeDropdown: null,
        currentTextColor: '#000000',
        currentHighlightColor: 'transparent',
        textColors: {!! $textColorsJs !!},
        highlightColors: {!! $bgColorsJs !!},
        showLists: {{ $showLists ? 'true' : 'false' }},
        showColors: {{ $showColors ? 'true' : 'false' }},
        showIndent: {{ $showIndent ? 'true' : 'false' }},
        showHr: {{ $showHr ? 'true' : 'false' }},
        showStrike: {{ $showStrike ? 'true' : 'false' }},
        showTodo: {{ $showTodo ? 'true' : 'false' }},
        showHighlight: {{ $showHighlight ? 'true' : 'false' }},
        savedRange: null,
        wordCount: 0,
        charCount: 0,
        saveTimeout: null,
        hoverCloseTimeout: null,
        
        // Block Drag & Drop State
        blockDragHandle: { show: false, top: 0, left: 0, block: null },
        dragIndicator: { show: false, top: 0 },
        isDraggingBlock: false,
        draggedBlock: null,
        dropTargetBlock: null,
        dropPosition: null,

        init() {
            this.$nextTick(() => {
                this.initEditorElement();
            });
        },

        initEditorElement() {
            const editorEl = document.getElementById('{{ $editorId }}');
            if (!editorEl) return;

            document.execCommand('defaultParagraphSeparator', false, 'p');

            let initialBody = $wire.{{ $contentProp }} || '';

            if (initialBody.trim() === '') {
                initialBody = '<p><br></p>';
            }
            editorEl.innerHTML = initialBody;
            this.updateCounter();
            this.refreshToolbarState();

            editorEl.addEventListener('input', (e) => {
                this.updateCounter();
                this.scheduleSave();
                this.refreshToolbarState();
                if (e && (e.data === ' ' || e.data === '\u00A0' || e.inputType === 'insertText')) {
                    this.handleMarkdownShortcuts();
                }
            });

            editorEl.addEventListener('keyup', (e) => {
                if (e.key === ' ' || e.key === 'Spacebar' || e.code === 'Space' || e.key === 'Unidentified') {
                    this.handleMarkdownShortcuts();
                }
                if (e.key !== 'Enter' && e.key !== 'Tab') {
                    this.refreshToolbarState();
                }
                if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Backspace', 'Delete', 'Enter'].includes(e.key)) {
                    this.saveSelection();
                }
            });

            editorEl.addEventListener('mouseup', () => {
                this.refreshToolbarState();
                this.saveSelection();
            });
            editorEl.addEventListener('focus', () => this.refreshToolbarState());
            editorEl.addEventListener('mousedown', () => this.saveSelection());

            document.addEventListener('selectionchange', () => {
                try {
                    const sel = window.getSelection();
                    if (sel && sel.rangeCount > 0 && editorEl.contains(sel.anchorNode)) {
                        this.savedRange = sel.getRangeAt(0).cloneRange();
                    }
                } catch (e) { }
            });

            editorEl.addEventListener('keydown', (e) => {
                this.handleKeydown(e);
            });

            editorEl.addEventListener('paste', (e) => {
                const items = (e.clipboardData || e.originalEvent.clipboardData).items;
                for (let index in items) {
                    const item = items[index];
                    if (item.kind === 'file' && item.type.includes('image/')) {
                        e.preventDefault();
                        const blob = item.getAsFile();
                        this.uploadAndInsertImage(blob);
                        return;
                    }
                }
            });
        },

        uploadAndInsertImage(file) {
            if (typeof $wire.upload !== 'function') return;
            $wire.upload('uploadFile', file, (uploadedFilename) => {
                if (typeof $wire.saveUploadedFile === 'function') {
                    $wire.saveUploadedFile().then(url => {
                        if (url) {
                            this.restoreSelection();
                            document.execCommand('insertImage', false, url);
                            this.scheduleSave();
                        }
                    });
                }
            });
        },

        saveSelection() {
            const editorEl = document.getElementById('{{ $editorId }}');
            const sel = window.getSelection();
            if (sel && sel.rangeCount > 0 && editorEl && editorEl.contains(sel.anchorNode)) {
                this.savedRange = sel.getRangeAt(0).cloneRange();
            }
        },

        restoreSelection() {
            const editorEl = document.getElementById('{{ $editorId }}');
            if (!editorEl) return;
            editorEl.focus();
            if (this.savedRange) {
                try {
                    const sel = window.getSelection();
                    sel.removeAllRanges();
                    sel.addRange(this.savedRange);
                } catch (e) { }
            }
        },

        exec(cmd, val = null) {
            const editorEl = document.getElementById('{{ $editorId }}');
            if (document.activeElement !== editorEl) {
                this.restoreSelection();
            }

            // PERBAIKAN: Tangani ALIGNMENT secara eksplisit untuk keseluruhan blok seperti di notes
            if (['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'].includes(cmd)) {
                const sel = window.getSelection();
                if (sel && sel.anchorNode) {
                    let node = sel.anchorNode;
                    if (node.nodeType === 3) node = node.parentElement;
                    const block = node.closest('p, h1, h2, h3, blockquote, li, div');

                    if (block && editorEl.contains(block)) {
                        const aligns = {
                            justifyLeft: 'left',
                            justifyCenter: 'center',
                            justifyRight: 'right',
                            justifyFull: 'justify'
                        };
                        block.style.textAlign = aligns[cmd];
                        this.refreshToolbarState();
                        this.scheduleSave();
                        return;
                    }
                }
            }

            document.execCommand(cmd, false, val);
            this.refreshToolbarState();
            this.scheduleSave();
        },

        setFormat(tag, label, targetBlock = null) {
            const editorEl = document.getElementById('{{ $editorId }}');
            if (document.activeElement !== editorEl) {
                this.restoreSelection();
            }

            const sel = window.getSelection();
            let block = targetBlock;
            if (!block && sel && sel.anchorNode) {
                let node = sel.anchorNode;
                if (node.nodeType === 3) node = node.parentElement;
                block = node.closest('p, h1, h2, h3, blockquote, div');
                if (block === editorEl) {
                    block = node.closest('p, h1, h2, h3, blockquote');
                }
            }

            if (block && block !== editorEl && editorEl && editorEl.contains(block)) {
                let startNode = null, startOffset = 0, endNode = null, endOffset = 0;
                if (sel && sel.rangeCount > 0) {
                    const range = sel.getRangeAt(0);
                    startOffset = range.startOffset;
                    endOffset = range.endOffset;
                    startNode = range.startContainer;
                    endNode = range.endContainer;
                }

                const newBlock = document.createElement(tag === 'p' ? 'p' : tag);
                if (block.style.textAlign) newBlock.style.textAlign = block.style.textAlign;

                while (block.firstChild) {
                    newBlock.appendChild(block.firstChild);
                }
                if (!newBlock.innerHTML.replace(/^[\s\u200B\uFEFF]+|[\s\u200B\uFEFF]+$/g, '') || newBlock.innerHTML === '') {
                    newBlock.innerHTML = '<br>';
                }

                block.parentNode.replaceChild(newBlock, block);

                try {
                    const newRange = document.createRange();
                    if (startNode && newBlock.contains(startNode)) {
                        newRange.setStart(startNode, startOffset);
                        newRange.setEnd(endNode, endOffset);
                    } else if (newBlock.firstChild && newBlock.firstChild.nodeType === 3) {
                        newRange.setStart(newBlock.firstChild, 0);
                        newRange.collapse(true);
                    } else {
                        newRange.selectNodeContents(newBlock);
                        newRange.collapse(false);
                    }
                    if (sel) {
                        sel.removeAllRanges();
                        sel.addRange(newRange);
                    }
                } catch (e) {
                    try {
                        const fallbackRange = document.createRange();
                        fallbackRange.selectNodeContents(newBlock);
                        fallbackRange.collapse(false);
                        if (sel) {
                            sel.removeAllRanges();
                            sel.addRange(fallbackRange);
                        }
                    } catch (err) {}
                }
            } else {
                document.execCommand('formatBlock', false, tag === 'p' ? 'p' : tag);
            }

            this.formatValue = tag;
            if (label) this.formatLabel = label;
            this.scheduleSave();
            this.refreshToolbarState();
        },

        refreshToolbarState() {
            const editorEl = document.getElementById('{{ $editorId }}');
            if (!editorEl) return;

            try {
                this.activeStates.bold = document.queryCommandState('bold');
                this.activeStates.italic = document.queryCommandState('italic');
                this.activeStates.underline = document.queryCommandState('underline');
                this.activeStates.strikeThrough = document.queryCommandState('strikeThrough');
                this.activeStates.insertUnorderedList = document.queryCommandState('insertUnorderedList');
                this.activeStates.insertOrderedList = document.queryCommandState('insertOrderedList');
                this.activeStates.todo = false;

                const sel = window.getSelection();
                if (sel && sel.anchorNode) {
                    let node = sel.anchorNode;
                    if (node.nodeType === 3) node = node.parentElement;
                    const block = node ? node.closest('h1, h2, h3, blockquote, p, li, div') : null;
                    if (block && editorEl.contains(block)) {
                        if (block.classList.contains('todo-item') || node.closest('.todo-item')) {
                            this.activeStates.todo = true;
                            this.activeStates.insertUnorderedList = false;
                        }

                        const tag = block.tagName.toLowerCase();
                        const map = { p: 'Normal Text', h1: 'Heading 1', h2: 'Heading 2', h3: 'Heading 3', blockquote: 'Quote' };
                        if (map[tag]) {
                            this.formatValue = tag;
                            this.formatLabel = map[tag];
                        }

                        // PERBAIKAN: Override status Toolbar UI langsung dari ComputedStyle seperti di notes
                        const style = window.getComputedStyle(block);
                        const align = style.textAlign;
                        this.activeStates.justifyLeft = align === 'left' || align === 'start';
                        this.activeStates.justifyCenter = align === 'center';
                        this.activeStates.justifyRight = align === 'right' || align === 'end';
                        this.activeStates.justifyFull = align === 'justify';

                        // Detect text color and highlight color at cursor position
                        let currEl = node.nodeType === 1 ? node : node.parentElement;
                        let inlineFore = null;
                        let inlineHilite = null;
                        let searchEl = currEl;
                        while (searchEl && searchEl !== editorEl && searchEl !== document.body) {
                            if (searchEl.nodeType === 1) {
                                if (!inlineFore && searchEl.style && searchEl.style.color) inlineFore = searchEl.style.color;
                                if (!inlineFore && searchEl.getAttribute && searchEl.getAttribute('color')) inlineFore = searchEl.getAttribute('color');
                                if (!inlineHilite && searchEl.style && searchEl.style.backgroundColor && searchEl.style.backgroundColor !== 'transparent' && searchEl.style.backgroundColor !== 'rgba(0, 0, 0, 0)') inlineHilite = searchEl.style.backgroundColor;
                            }
                            searchEl = searchEl.parentElement;
                        }
                        if (!inlineFore) {
                            try { inlineFore = document.queryCommandValue('foreColor'); } catch(e) {}
                        }
                        if (!inlineHilite) {
                            try {
                                const val = document.queryCommandValue('hiliteColor') || document.queryCommandValue('backColor');
                                if (val && val !== 'transparent' && val !== 'rgba(0, 0, 0, 0)' && val !== 'rgb(255, 255, 255)' && val !== '#ffffff' && val !== '#FFFFFF') inlineHilite = val;
                            } catch(e) {}
                        }
                        const normFore = this.normalizeColor(inlineFore);
                        const normHilite = this.normalizeColor(inlineHilite);
                        this.currentTextColor = this.textColors.find(c => this.normalizeColor(c) === normFore) || '#000000';
                        this.currentHighlightColor = this.highlightColors.find(c => c !== 'transparent' && this.normalizeColor(c) === normHilite) || 'transparent';
                    }
                }
            } catch (e) { /* ignore */ }
        },

        normalizeColor(c) {
            if (!c || c === 'transparent' || c === 'rgba(0, 0, 0, 0)' || c === 'rgb(255, 255, 255)' || c === '#ffffff' || c === '#FFFFFF') return 'transparent';
            if (typeof c !== 'string') return 'transparent';
            if (c.startsWith('#')) return c.toUpperCase();
            const match = c.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/i);
            if (match) {
                const r = parseInt(match[1]).toString(16).padStart(2, '0');
                const g = parseInt(match[2]).toString(16).padStart(2, '0');
                const b = parseInt(match[3]).toString(16).padStart(2, '0');
                return (`#${r}${g}${b}`).toUpperCase();
            }
            return c.toUpperCase();
        },

        insertTodoBlock(isChecked = false) {
            if (!this.showTodo) return;
            const editorEl = document.getElementById('{{ $editorId }}');
            const sel = window.getSelection();
            if (!sel || !sel.anchorNode) return;

            let node = sel.anchorNode;
            if (node.nodeType === 3) node = node.parentElement;
            const existingTodo = node.closest('.todo-item');

            if (existingTodo && editorEl.contains(existingTodo)) {
                const p = document.createElement('p');
                const textSpan = existingTodo.querySelector('.todo-text');
                p.innerHTML = textSpan ? textSpan.innerHTML : (existingTodo.textContent || '<br>');
                existingTodo.parentNode.replaceChild(p, existingTodo);
                const range = document.createRange();
                range.selectNodeContents(p);
                range.collapse(false);
                sel.removeAllRanges();
                sel.addRange(range);
                this.scheduleSave();
                this.refreshToolbarState();
                return;
            }

            let block = node.closest('p, h1, h2, h3, div, blockquote, li');
            if (!block || !editorEl.contains(block) || block === editorEl) {
                if (editorEl.contains(node) && node !== editorEl && node.parentNode === editorEl) {
                    block = node;
                } else {
                    block = document.createElement('p');
                    block.innerHTML = '<br>';
                    editorEl.appendChild(block);
                }
            }

            let contentHtml = block.innerHTML || '<br>';
            contentHtml = contentHtml.replace(/^(\s|&nbsp;)*\[\s*[xX]?\s*\](\s|&nbsp;)*/i, '');
            if (!contentHtml.trim() || contentHtml.trim() === '<br>') contentHtml = '<br>';

            const todoDiv = document.createElement('div');
            todoDiv.className = 'todo-item flex items-start gap-2 my-1';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'todo-checkbox mt-1 cursor-pointer w-4 h-4 rounded border-secondary-100 text-secondary-200 accent-secondary-200 focus:ring-secondary-200';
            checkbox.setAttribute('contenteditable', 'false');
            if (isChecked) {
                checkbox.setAttribute('checked', 'checked');
            }
            const onClickCode = 'this.nextElementSibling.style.textDecoration = this.checked ? \'line-through\' : \'none\'; this.nextElementSibling.style.opacity = this.checked ? \'0.5\' : \'1\';';
            checkbox.setAttribute('onclick', onClickCode);

            const span = document.createElement('span');
            span.className = 'todo-text flex-1 outline-none';
            if (isChecked) {
                span.style.textDecoration = 'line-through';
                span.style.opacity = '0.5';
            }
            span.innerHTML = contentHtml;

            todoDiv.appendChild(checkbox);
            todoDiv.appendChild(span);
            block.parentNode.replaceChild(todoDiv, block);

            const textSpan = todoDiv.querySelector('.todo-text');
            const range = document.createRange();
            range.selectNodeContents(textSpan);
            range.collapse(false);
            sel.removeAllRanges();
            sel.addRange(range);
            this.scheduleSave();
            this.refreshToolbarState();
        },

        handleMarkdownShortcuts() {
            const editorEl = document.getElementById('{{ $editorId }}');
            const sel = window.getSelection();
            if (!sel || !sel.anchorNode) return;

            let node = sel.anchorNode;
            if (node.nodeType !== 3 && node.childNodes && node.childNodes.length > 0) {
                const idx = Math.max(0, sel.anchorOffset - 1);
                if (node.childNodes[idx] && node.childNodes[idx].nodeType === 3) {
                    node = node.childNodes[idx];
                } else if (node.firstChild && node.firstChild.nodeType === 3) {
                    node = node.firstChild;
                }
            }

            if (node.nodeType === 3) {
                const textBefore = node.textContent.slice(0, sel.anchorOffset);
                const block = node.parentElement.closest('p, h1, h2, h3, blockquote, div');

                if (block && editorEl.contains(block)) {
                    const hasTrailingSpace = /[\s\u00A0]$/.test(textBefore);
                    const trimmed = textBefore.replace(/^[\s\u200B\uFEFF]+|[\s\u200B\uFEFF]+$/g, '');

                    if (hasTrailingSpace && trimmed === '#') {
                        this.deleteShortcutPrefix(node, sel.anchorOffset);
                        this.setFormat('h1', 'Heading 1', block);
                        return;
                    }
                    if (hasTrailingSpace && trimmed === '##') {
                        this.deleteShortcutPrefix(node, sel.anchorOffset);
                        this.setFormat('h2', 'Heading 2', block);
                        return;
                    }
                    if (hasTrailingSpace && trimmed === '###') {
                        this.deleteShortcutPrefix(node, sel.anchorOffset);
                        this.setFormat('h3', 'Heading 3', block);
                        return;
                    }
                    if (hasTrailingSpace && (trimmed === '>' || trimmed === '{}' || trimmed === '&gt;')) {
                        this.deleteShortcutPrefix(node, sel.anchorOffset);
                        this.setFormat('blockquote', 'Quote', block);
                        return;
                    }

                    if (this.showLists && hasTrailingSpace && (trimmed === '-' || trimmed === '*' || trimmed === '+')) {
                        this.deleteShortcutPrefix(node, sel.anchorOffset);
                        this.exec('insertUnorderedList');
                        return;
                    }

                    if (this.showLists && hasTrailingSpace && /^[0-9]+\.$/.test(trimmed)) {
                        this.deleteShortcutPrefix(node, sel.anchorOffset);
                        this.exec('insertOrderedList');
                        return;
                    }

                    if (this.showLists && hasTrailingSpace && /^[aA]\.$/.test(trimmed)) {
                        const isUpper = /^[A]\.$/.test(trimmed);
                        this.deleteShortcutPrefix(node, sel.anchorOffset);
                        this.exec('insertOrderedList');
                        setTimeout(() => {
                            const currSel = window.getSelection();
                            const currNode = currSel && currSel.anchorNode;
                            const targetElem = currNode && currNode.nodeType === 3 ? currNode.parentElement : currNode;
                            const ol = targetElem ? targetElem.closest('ol') : null;
                            if (ol) {
                                ol.setAttribute('type', isUpper ? 'A' : 'a');
                                ol.style.listStyleType = isUpper ? 'upper-alpha' : 'lower-alpha';
                                this.scheduleSave();
                            }
                        }, 10);
                        return;
                    }

                    if (this.showTodo && hasTrailingSpace && /^\[[xX]?\]$/.test(trimmed)) {
                        const isChecked = /^\[[xX]\]$/.test(trimmed);
                        this.deleteShortcutPrefix(node, sel.anchorOffset);
                        this.insertTodoBlock(isChecked);
                        return;
                    }

                    if (this.showHr && hasTrailingSpace && trimmed === '---') {
                        this.deleteShortcutPrefix(node, sel.anchorOffset);
                        this.exec('insertHorizontalRule');
                        return;
                    }

                    const inlinePatterns = [
                        { regex: /\*\*([^*]+)\*\*\s$/, tag: 'strong', prop: null, contentIdx: 1, offsetAdj: 0 },
                        { regex: /(^|[^*])\*([^*]+)\*\s$/, tag: 'em', prop: null, contentIdx: 2, offsetAdj: 1 },
                        { regex: /~~([^~]+)~~\s$/, tag: 's', prop: 'showStrike', contentIdx: 1, offsetAdj: 0 },
                        { regex: /==([^=]+)==\s$/, tag: 'mark', prop: 'showHighlight', contentIdx: 1, offsetAdj: 0 }
                    ];

                    for (let pattern of inlinePatterns) {
                        if (pattern.prop && !this[pattern.prop]) continue;
                        const match = textBefore.match(pattern.regex);
                        if (match) {
                            const fullMatch = match[0];
                            const content = match[pattern.contentIdx];
                            const leadingLen = pattern.offsetAdj ? (match[1] ? match[1].length : 0) : 0;
                            const startIdx = sel.anchorOffset - fullMatch.length + leadingLen;

                            const range = document.createRange();
                            range.setStart(node, startIdx);
                            range.setEnd(node, sel.anchorOffset);
                            range.deleteContents();

                            let el;
                            if (pattern.tag === 'mark') {
                                el = document.createElement('span');
                                const bg = (this.currentHighlightColor && this.currentHighlightColor !== 'transparent') ? this.currentHighlightColor : '#ffeaa7';
                                el.style.backgroundColor = bg;
                            } else {
                                el = document.createElement(pattern.tag);
                            }
                            el.textContent = content;
                            range.insertNode(el);

                            const spaceNode = document.createTextNode(' ');
                            if (el.nextSibling) {
                                el.parentNode.insertBefore(spaceNode, el.nextSibling);
                            } else {
                                el.parentNode.appendChild(spaceNode);
                            }

                            const newSel = window.getSelection();
                            const newRange = document.createRange();
                            newRange.setStart(spaceNode, 1);
                            newRange.collapse(true);
                            newSel.removeAllRanges();
                            newSel.addRange(newRange);

                            this.refreshToolbarState();
                            this.scheduleSave();
                            return;
                        }
                    }
                }
            }
        },

        deleteShortcutPrefix(node, endOffset) {
            try {
                const range = document.createRange();
                range.setStart(node, 0);
                range.setEnd(node, endOffset);
                range.deleteContents();
            } catch (e) {}
        },

        handleKeydown(e) {
            const editorEl = document.getElementById('{{ $editorId }}');
            if (e.key === 'Tab') {
                e.preventDefault();
                if (e.shiftKey) {
                    this.exec('outdent');
                } else {
                    this.exec('indent');
                }
                return;
            }

            if (e.key === 'Backspace') {
                const sel = window.getSelection();
                if (sel && sel.anchorNode) {
                    let node = sel.anchorNode;
                    if (node.nodeType === 3) node = node.parentElement;
                    const li = node.closest('li');
                    if (li && li.textContent.trim() === '' && editorEl.contains(li)) {
                        e.preventDefault();
                        this.exec('outdent');
                        return;
                    }

                    const todoItem = node.closest('.todo-item');
                    if (todoItem && editorEl.contains(todoItem)) {
                        const textSpan = todoItem.querySelector('.todo-text');
                        const isAtStart = sel.isCollapsed && sel.anchorOffset === 0 && (sel.anchorNode === textSpan || sel.anchorNode === textSpan.firstChild || sel.anchorNode === todoItem);
                        if (todoItem.textContent.trim() === '' || isAtStart) {
                            e.preventDefault();
                            const p = document.createElement('p');
                            p.innerHTML = textSpan ? (textSpan.innerHTML || '<br>') : (todoItem.textContent || '<br>');
                            if (!p.innerHTML.trim() || p.innerHTML.trim() === '') p.innerHTML = '<br>';
                            todoItem.parentNode.replaceChild(p, todoItem);
                            const range = document.createRange();
                            range.selectNodeContents(p);
                            range.collapse(true);
                            sel.removeAllRanges();
                            sel.addRange(range);
                            this.scheduleSave();
                            return;
                        }
                    }

                    const formatBlock = node.closest('blockquote, h1, h2, h3');
                    if (formatBlock && editorEl.contains(formatBlock)) {
                        let isAtStart = false;
                        if (sel.isCollapsed && sel.anchorOffset === 0) {
                            try {
                                const rangeBefore = document.createRange();
                                rangeBefore.setStart(formatBlock, 0);
                                rangeBefore.setEnd(sel.anchorNode, sel.anchorOffset);
                                if (rangeBefore.toString().replace(/^[\s\u200B\uFEFF]+|[\s\u200B\uFEFF]+$/g, '') === '') {
                                    isAtStart = true;
                                }
                            } catch (err) {}
                        }
                        if (formatBlock.textContent.replace(/^[\s\u200B\uFEFF]+|[\s\u200B\uFEFF]+$/g, '') === '' || isAtStart) {
                            e.preventDefault();
                            this.setFormat('p', 'Normal Text', formatBlock);
                            return;
                        }
                    }
                }
            }

            if (e.key === 'Enter') {
                if (e.shiftKey) {
                    e.preventDefault();
                    document.execCommand('insertLineBreak');
                    this.scheduleSave();
                    return;
                }

                const sel = window.getSelection();
                if (sel && sel.anchorNode) {
                    let node = sel.anchorNode;
                    if (node.nodeType === 3) node = node.parentElement;
                    
                    const todoItem = node.closest('.todo-item');
                    if (todoItem && editorEl.contains(todoItem)) {
                        e.preventDefault();
                        if (!this.showTodo) {
                            const p = document.createElement('p');
                            p.innerHTML = '<br>';
                            if (todoItem.nextSibling) todoItem.parentNode.insertBefore(p, todoItem.nextSibling);
                            else todoItem.parentNode.appendChild(p);
                            const range = document.createRange();
                            range.selectNodeContents(p);
                            range.collapse(true);
                            sel.removeAllRanges();
                            sel.addRange(range);
                        } else if (todoItem.textContent.trim() === '') {
                            const p = document.createElement('p');
                            p.innerHTML = '<br>';
                            todoItem.parentNode.replaceChild(p, todoItem);
                            const range = document.createRange();
                            range.selectNodeContents(p);
                            range.collapse(true);
                            sel.removeAllRanges();
                            sel.addRange(range);
                        } else {
                            const newTodo = document.createElement('div');
                            newTodo.className = 'todo-item flex items-start gap-2 my-1';

                            const checkbox = document.createElement('input');
                            checkbox.type = 'checkbox';
                            checkbox.className = 'todo-checkbox mt-1 cursor-pointer w-4 h-4 rounded border-secondary-100 text-secondary-200 accent-secondary-200 focus:ring-secondary-200';
                            checkbox.setAttribute('contenteditable', 'false');
                            const onClickCode = 'this.nextElementSibling.style.textDecoration = this.checked ? \'line-through\' : \'none\'; this.nextElementSibling.style.opacity = this.checked ? \'0.5\' : \'1\';';
                            checkbox.setAttribute('onclick', onClickCode);

                            const span = document.createElement('span');
                            span.className = 'todo-text flex-1 outline-none';
                            span.innerHTML = '<br>';

                            newTodo.appendChild(checkbox);
                            newTodo.appendChild(span);

                            if (todoItem.nextSibling) {
                                todoItem.parentNode.insertBefore(newTodo, todoItem.nextSibling);
                            } else {
                                todoItem.parentNode.appendChild(newTodo);
                            }
                            const textSpan = newTodo.querySelector('.todo-text');
                            const range = document.createRange();
                            range.selectNodeContents(textSpan);
                            range.collapse(true);
                            sel.removeAllRanges();
                            sel.addRange(range);
                        }
                        this.scheduleSave();
                        return;
                    }

                    const currentBlock = node.closest('h1, h2, h3, blockquote');
                    if (currentBlock && editorEl.contains(currentBlock)) {
                        setTimeout(() => {
                            const newSel = window.getSelection();
                            if (!newSel || !newSel.anchorNode) return;
                            let newNode = newSel.anchorNode;
                            if (newNode.nodeType === 3) newNode = newNode.parentElement;
                            
                            const newBlock = newNode.closest('h1, h2, h3, blockquote');
                            if (newBlock && editorEl.contains(newBlock)) {
                                if (newBlock !== currentBlock) {
                                    this.setFormat('p', 'Normal Text');
                                } else {
                                    const p = document.createElement('p');
                                    p.innerHTML = '<br>';
                                    if (currentBlock.nextSibling) {
                                        currentBlock.parentNode.insertBefore(p, currentBlock.nextSibling);
                                    } else {
                                        currentBlock.parentNode.appendChild(p);
                                    }
                                    const range = document.createRange();
                                    range.selectNodeContents(p);
                                    range.collapse(true);
                                    newSel.removeAllRanges();
                                    newSel.addRange(range);
                                    this.formatValue = 'p';
                                    this.formatLabel = 'Normal Text';
                                    this.refreshToolbarState();
                                    this.scheduleSave();
                                }
                            }
                        }, 10);
                    }
                }
            }

            if (e.altKey && (e.key === 'ArrowUp' || e.key === 'ArrowDown')) {
                e.preventDefault();
                const sel = window.getSelection();
                if (sel && sel.anchorNode) {
                    let node = sel.anchorNode;
                    if (node.nodeType === 3) node = node.parentElement;
                    let block = node.closest('p, h1, h2, h3, blockquote, li, .todo-item');
                    if (!block && editorEl.contains(node) && node !== editorEl && node.parentNode === editorEl) {
                        block = node;
                    }
                    if (block && editorEl.contains(block) && block !== editorEl) {
                        let startNode = null, startOffset = 0, endNode = null, endOffset = 0;
                        if (sel.rangeCount > 0) {
                            const range = sel.getRangeAt(0);
                            startNode = range.startContainer;
                            startOffset = range.startOffset;
                            endNode = range.endContainer;
                            endOffset = range.endOffset;
                        }

                        let moved = false;
                        if (e.key === 'ArrowUp' && block.previousElementSibling) {
                            block.parentNode.insertBefore(block, block.previousElementSibling);
                            moved = true;
                        } else if (e.key === 'ArrowDown' && block.nextElementSibling) {
                            block.parentNode.insertBefore(block.nextElementSibling, block);
                            moved = true;
                        }

                        if (moved) {
                            block.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                            try {
                                const newRange = document.createRange();
                                if (startNode && block.contains(startNode)) {
                                    newRange.setStart(startNode, startOffset);
                                    newRange.setEnd(endNode, endOffset);
                                } else {
                                    newRange.selectNodeContents(block);
                                    newRange.collapse(false);
                                }
                                sel.removeAllRanges();
                                sel.addRange(newRange);
                            } catch (err) {
                                try {
                                    const fallbackRange = document.createRange();
                                    fallbackRange.selectNodeContents(block);
                                    fallbackRange.collapse(false);
                                    sel.removeAllRanges();
                                    sel.addRange(fallbackRange);
                                } catch (e2) {}
                            }
                            this.scheduleSave();
                        }
                    }
                }
            }
        },

        scheduleSave() {
            if (this.saveTimeout) clearTimeout(this.saveTimeout);
            this.saveTimeout = setTimeout(() => {
                const editorEl = document.getElementById('{{ $editorId }}');
                if (editorEl && typeof $wire.{{ $updateMethod }} === 'function') {
                    $wire.{{ $updateMethod }}(editorEl.innerHTML);
                }
            }, 600);
        },

        updateCounter() {
            const editorEl = document.getElementById('{{ $editorId }}');
            if (!editorEl) return;
            const text = (editorEl.innerText || '').replace(/\u200B/g, '').trim();
            this.wordCount = text === '' ? 0 : text.split(/\s+/).filter(Boolean).length;
            this.charCount = text.length;
        },

        // Block Drag & Drop State
        onEditorMouseMove(e) {
            if (this.isDraggingBlock) return;
            const editor = document.getElementById('{{ $editorId }}');
            if (!editor) return;
            const editorRect = editor.getBoundingClientRect();

            let block = null;
            const elements = document.elementsFromPoint(e.clientX, e.clientY);

            for (let el of elements) {
                if (el.parentElement === editor) {
                    block = el;
                    break;
                }
            }

            if (!block) {
                const children = Array.from(editor.children);
                for (let child of children) {
                    const rect = child.getBoundingClientRect();
                    if (e.clientY >= rect.top && e.clientY <= rect.bottom) {
                        block = child;
                        break;
                    }
                }
            }

            if (block && editor.contains(block)) {
                const rect = block.getBoundingClientRect();
                this.blockDragHandle.show = true;
                this.blockDragHandle.left = 12;

                let topPos = rect.top - editorRect.top + editor.scrollTop + (rect.height / 2) - 12;
                this.blockDragHandle.top = topPos;
                this.blockDragHandle.block = block;
            } else {
                this.blockDragHandle.show = false;
                this.blockDragHandle.block = null;
            }
        },

        onBlockDragStart(e) {
            if (!this.blockDragHandle.block) {
                e.preventDefault();
                return;
            }
            this.isDraggingBlock = true;
            this.draggedBlock = this.blockDragHandle.block;

            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', this.draggedBlock.outerHTML);
            e.dataTransfer.setDragImage(this.draggedBlock, 0, 0);

            setTimeout(() => {
                this.draggedBlock.style.opacity = '0.3';
            }, 0);
        },

        onBlockDragEnd(e) {
            this.isDraggingBlock = false;
            this.dragIndicator.show = false;
            if (this.draggedBlock) {
                this.draggedBlock.style.opacity = '1';
                this.draggedBlock = null;
            }
            this.dropTargetBlock = null;
        },

        onEditorDragOver(e) {
            if (!this.isDraggingBlock || !this.draggedBlock) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';

            const editor = document.getElementById('{{ $editorId }}');
            if (!editor) return;
            const children = Array.from(editor.children);

            let targetBlock = null;
            let minDistance = Infinity;

            children.forEach(child => {
                if (child === this.draggedBlock) return;
                const rect = child.getBoundingClientRect();
                const center = rect.top + rect.height / 2;
                const distance = Math.abs(e.clientY - center);
                if (distance < minDistance) {
                    minDistance = distance;
                    targetBlock = child;
                }
            });

            if (targetBlock) {
                this.dropTargetBlock = targetBlock;
                const rect = targetBlock.getBoundingClientRect();
                const editorRect = editor.getBoundingClientRect();

                const isAbove = e.clientY < (rect.top + rect.height / 2);
                this.dropPosition = isAbove ? 'before' : 'after';

                this.dragIndicator.show = true;
                let indTop = isAbove ? rect.top : rect.bottom;
                this.dragIndicator.top = indTop - editorRect.top + editor.scrollTop;
            }
        },

        onEditorDrop(e) {
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                const file = e.dataTransfer.files[0];
                if (file.type && file.type.startsWith('image/')) {
                    e.preventDefault();
                    this.uploadAndInsertImage(file);
                    return;
                }
            }

            if (!this.isDraggingBlock || !this.draggedBlock || !this.dropTargetBlock) return;
            e.preventDefault();

            const editor = document.getElementById('{{ $editorId }}');
            if (!editor) return;

            if (this.dropPosition === 'before') {
                editor.insertBefore(this.draggedBlock, this.dropTargetBlock);
            } else {
                editor.insertBefore(this.draggedBlock, this.dropTargetBlock.nextSibling);
            }

            this.scheduleSave();

            this.isDraggingBlock = false;
            this.dragIndicator.show = false;
            this.draggedBlock.style.opacity = '1';
            this.draggedBlock = null;
            this.dropTargetBlock = null;
        },

        onToolbarAreaEnter(area) {
            if (this.hoverCloseTimeout) {
                clearTimeout(this.hoverCloseTimeout);
                this.hoverCloseTimeout = null;
            }
        },

        onToolbarAreaLeave(area) {
            if (this.hoverCloseTimeout) clearTimeout(this.hoverCloseTimeout);
            this.hoverCloseTimeout = setTimeout(() => {
                this.activeDropdown = null;
                this.hoverCloseTimeout = null;
            }, 120);
        },

        adjustOverlayPosition(pos, width = 180, height = 220, targetEl = null) {
            if (targetEl) {
                const r = targetEl.getBoundingClientRect();
                pos.left = r.left + (r.width / 2) - (width / 2);
                pos.top = r.bottom + 6;
            }
            const editorEl = document.getElementById('{{ $editorId }}');
            const rootBoxEl = editorEl ? (editorEl.closest('.flex-1.flex.flex-col') || editorEl.parentElement.parentElement) : (this.$el || document.body);
            if (rootBoxEl) {
                const box = rootBoxEl.getBoundingClientRect();
                const minLeft = Math.max(box.left + 8, 8);
                const maxLeft = Math.min(box.right - width - 8, window.innerWidth - width - 8);
                if (maxLeft >= minLeft) {
                    pos.left = Math.min(Math.max(pos.left, minLeft), maxLeft);
                } else {
                    pos.left = Math.max(pos.left, 8);
                }
            } else {
                pos.left = Math.min(Math.max(pos.left, 12), Math.max(window.innerWidth - width - 12, 12));
            }
            pos.top = Math.min(Math.max(pos.top, 8), Math.max(window.innerHeight - height - 8, 8));
        }
    }"
>
    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: var(--color-secondary-100); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: var(--color-secondary-150); }

        #{{ $editorId }}:focus { outline: none; }
        #{{ $editorId }} p { margin-bottom: 1em; }
        #{{ $editorId }} h1 { font-size: 2em; font-weight: normal; margin-bottom: 0.5em; }
        #{{ $editorId }} h2 { font-size: 1.5em; font-weight: normal; margin-bottom: 0.5em; }
        #{{ $editorId }} h3 { font-size: 1.17em; font-weight: normal; margin-bottom: 0.5em; }
        #{{ $editorId }} h1, #{{ $editorId }} h2, #{{ $editorId }} h3,
        #{{ $editorId }} h1 *, #{{ $editorId }} h2 *, #{{ $editorId }} h3 * {
            font-weight: normal !important;
        }
        
        #{{ $editorId }} ul { list-style-type: disc; padding-left: 1.5em; }
        #{{ $editorId }} ul ul { list-style-type: circle; }
        #{{ $editorId }} ul ul ul { list-style-type: square; }
        #{{ $editorId }} ul ul ul ul { list-style-type: disc; }
        #{{ $editorId }} ul ul ul ul ul { list-style-type: circle; }
        #{{ $editorId }} ul ul ul ul ul ul { list-style-type: square; }

        #{{ $editorId }} ol { list-style: decimal; padding-left: 1.5em; }
        #{{ $editorId }} hr { border: none; border-top: 1px solid var(--color-brand-200); margin: 1em 0; }
        #{{ $editorId }} blockquote { border-left: 3px solid var(--color-brand-200); padding-left: 1em; color: var(--color-text-70); }

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
                title="Scroll Left"
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
                class="h-7 px-2.5 flex items-center gap-1.5 text-[12px] text-text-80 bg-card-bg border border-brand-200 rounded-md outline-none cursor-pointer hover:border-secondary-100 transition-colors shadow-sm"
                style="min-width: 118px;"
                title="Text Format"
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
                    class="w-40 bg-white border border-brand-200 rounded-lg py-1"
                    style="display: none;"
                >
                    <button type="button" @mousedown.prevent="saveSelection()" @click="setFormat('p', 'Normal Text'); activeDropdown = null" class="format-option w-full text-left px-3 py-1.5 text-[13px] text-text-90 hover:bg-brand-50" x-bind:class="formatValue === 'p' ? 'bg-brand-150 font-semibold' : ''" title="Normal Text">Normal Text</button>
                    <button type="button" @mousedown.prevent="saveSelection()" @click="setFormat('h1', 'Heading 1'); activeDropdown = null" class="format-option w-full text-left px-3 py-1.5 text-[18px] font-bold text-text-90 hover:bg-brand-50" x-bind:class="formatValue === 'h1' ? 'bg-brand-150' : ''" title="Heading 1 (# + Space)">Heading 1</button>
                    <button type="button" @mousedown.prevent="saveSelection()" @click="setFormat('h2', 'Heading 2'); activeDropdown = null" class="format-option w-full text-left px-3 py-1.5 text-[15px] font-bold text-text-90 hover:bg-brand-50" x-bind:class="formatValue === 'h2' ? 'bg-brand-150' : ''" title="Heading 2 (## + Space)">Heading 2</button>
                    <button type="button" @mousedown.prevent="saveSelection()" @click="setFormat('h3', 'Heading 3'); activeDropdown = null" class="format-option w-full text-left px-3 py-1.5 text-[13.5px] font-bold text-text-90 hover:bg-brand-50" x-bind:class="formatValue === 'h3' ? 'bg-brand-150' : ''" title="Heading 3 (### + Space)">Heading 3</button>
                    <button type="button" @mousedown.prevent="saveSelection()" @click="setFormat('blockquote', 'Quote'); activeDropdown = null" class="format-option w-full text-left px-3 py-1.5 text-[13px] italic text-text-70 hover:bg-brand-50" x-bind:class="formatValue === 'blockquote' ? 'bg-brand-150' : ''" title="Quote (> + Space)">Quote</button>
                </div>
            </template>
        </div>

        <div class="toolbar-divider"></div>

        <button type="button" @mousedown.prevent="saveSelection()" @click="exec('bold')" class="toolbar-btn" x-bind:class="activeStates.bold ? 'is-active' : ''" title="Bold (Ctrl+B or **text**)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M15.6 10.79c.97-.67 1.65-1.77 1.65-2.79 0-2.26-1.75-4-4-4H7v14h7.04c2.09 0 3.71-1.7 3.71-3.79 0-1.52-.86-2.82-2.15-3.42zM10 6.5h3c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-3v-3zm3.5 9H10v-3h3.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5z"/></svg>
        </button>
        <button type="button" @mousedown.prevent="saveSelection()" @click="exec('italic')" class="toolbar-btn" x-bind:class="activeStates.italic ? 'is-active' : ''" title="Italic (Ctrl+I or *text*)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M10 4v3h2.21l-3.42 8H6v3h8v-3h-2.21l3.42-8H18V4z"/></svg>
        </button>
        <button type="button" @mousedown.prevent="saveSelection()" @click="exec('underline')" class="toolbar-btn" x-bind:class="activeStates.underline ? 'is-active' : ''" title="Underline (Ctrl+U)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17c3.31 0 6-2.69 6-6V3h-2.5v8c0 1.93-1.57 3.5-3.5 3.5S8.5 12.93 8.5 11V3H6v8c0 3.31 2.69 6 6 6zm-7 2v2h14v-2H5z"/></svg>
        </button>
        
        @if($showStrike)
            <button type="button" @mousedown.prevent="saveSelection()" @click="exec('strikeThrough')" class="toolbar-btn" x-bind:class="activeStates.strikeThrough ? 'is-active' : ''" title="Strikethrough (~~text~~)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M10 19h4v-3h-4v3zM5 4v3h5v3h4V7h5V4H5zM3 14h18v-2H3v2z"/></svg>
            </button>
        @endif

        @if($showLists || $showTodo)
            <div class="toolbar-divider"></div>
        @endif

        @if($showLists)
            <button type="button" @mousedown.prevent="saveSelection()" @click="exec('insertUnorderedList')" class="toolbar-btn" x-bind:class="activeStates.insertUnorderedList ? 'is-active' : ''" title="Bullet List (-, *, or + and Space)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M4 10.5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm0-6c-.83 0-1.5.67-1.5 1.5S3.17 7.5 4 7.5 5.5 6.83 5.5 6 4.83 4.5 4 4.5zm0 12c-.83 0-1.5.68-1.5 1.5s.68 1.5 1.5 1.5 1.5-.68 1.5-1.5-.67-1.5-1.5-1.5zM7 19h14v-2H7v2zm0-6h14v-2H7v2zm0-8v2h14V5H7z"/></svg>
            </button>
            <button type="button" @mousedown.prevent="saveSelection()" @click="exec('insertOrderedList')" class="toolbar-btn" x-bind:class="activeStates.insertOrderedList ? 'is-active' : ''" title="Numbered List (1. or a. and Space)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M2 17h2v.5H3v1h1v.5H2v1h3v-4H2v1zm1-9h1V4H2v1h1v3zm-1 3h1.8L2 13.1v.9h3v-1H3.2L5 10.9V10H2v1zm5-6v2h14V5H7zm0 14h14v-2H7v2zm0-6h14v-2H7v2z"/></svg>
            </button>
        @endif

        @if($showTodo)
            <button type="button" @mousedown.prevent="saveSelection()" @click="restoreSelection(); insertTodoBlock()" class="toolbar-btn" x-bind:class="activeStates.todo ? 'is-active' : ''" title="To-Do List ([] + Space)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </button>
        @endif

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
                        <span class="text-[10px] font-semibold text-secondary-150 uppercase tracking-wider mb-2 block">Text Color</span>
                        <div class="grid grid-cols-6 gap-2">
                            <template x-for="c in textColors" :key="c">
                                <button type="button" @mousedown.prevent="saveSelection()" @click="exec('foreColor', c); currentTextColor = c;" class="w-5 h-5 rounded-full transition-all duration-150 relative flex items-center justify-center shrink-0" x-bind:class="(currentTextColor || '#000000') === c ? 'border border-secondary-200 bg-white p-[3px]' : 'border border-brand-200 hover:scale-110 hover:border-secondary-150'" x-bind:style="(currentTextColor || '#000000') === c ? '' : `background:${c}`" :title="c === '#000000' ? 'Default / Black' : c">
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
                        <span class="text-[10px] font-semibold text-secondary-150 uppercase tracking-wider mb-2 block">Highlight Color</span>
                        <div class="grid grid-cols-7 gap-1.5">
                            <template x-for="c in highlightColors" :key="c">
                                <button type="button" @mousedown.prevent="saveSelection()" @click="exec('hiliteColor', c === 'transparent' ? 'transparent' : c); currentHighlightColor = c;" class="w-5 h-5 rounded-full transition-all duration-150 relative flex items-center justify-center shrink-0" x-bind:class="(currentHighlightColor || 'transparent') === c ? 'border border-secondary-200 bg-white p-[3px]' : 'border border-brand-200 hover:scale-110 hover:border-secondary-150'" x-bind:style="(currentHighlightColor || 'transparent') === c || c === 'transparent' ? '' : `background:${c}`" :title="c === 'transparent' ? 'No Highlight' : c">
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
            <button type="button" @mousedown.prevent="saveSelection()" @click="exec('outdent')" class="toolbar-btn" title="Decrease Indent">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M11 17h10v-2H11v2zm-8-5l4 4V8l-4 4zm0 9h18v-2H3v2zM3 3v2h18V3H3zm8 6h10V7H11v2zm0 4h10v-2H11v2z"/></svg>
            </button>
            <button type="button" @mousedown.prevent="saveSelection()" @click="exec('indent')" class="toolbar-btn" title="Increase Indent">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3 21h18v-2H3v2zM3 8v8l4-4-4-4zm8 9h10v-2H11v2zM3 3v2h18V3H3zm8 6h10V7H11v2zm0 4h10v-2H11v2z"/></svg>
            </button>
        @endif

        @if($showHr)
            <button type="button" @mousedown.prevent="saveSelection()" @click="exec('insertHorizontalRule')" class="toolbar-btn" title="Horizontal Rule (--- + Space)">
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
                title="Scroll Right"
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
            class="w-full h-full flex-1 p-8 lg:p-10 text-app-body-small text-[15px] text-text-90 leading-[1.65] overflow-y-auto custom-scrollbar bg-brand-50"
        ></div>

        {{-- Floating Block Drag Handle --}}
        <div x-show="blockDragHandle.show"
             class="absolute flex items-center justify-center cursor-grab text-secondary-100 hover:text-secondary-200 transition-colors select-none"
             :style="`top: ${blockDragHandle.top}px; left: ${blockDragHandle.left}px; width: 24px; height: 24px; z-index: 50;`"
             draggable="true"
             @dragstart="onBlockDragStart($event)"
             @dragend="onBlockDragEnd($event)"
             @mouseenter="blockDragHandle.show = true"
             title="Drag to move block"
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

    {{-- Word Count Footer inside Editor Sheet --}}
    <div class="px-8 lg:px-10 py-2.5 text-[12px] text-secondary-150 font-medium border-t border-transparent select-none shrink-0 bg-brand-50 right-8">
        <span x-text="wordCount + ' words'"></span>
    </div>
</div>