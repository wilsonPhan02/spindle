document.addEventListener('alpine:init', () => {
    Alpine.data('textEditor', (config) => ({
        ...config,
        formatValue: 'p',
        formatLabel: config.i18n ? config.i18n.normalText : 'Normal Text',
        activeStates: {
            bold: false, italic: false, underline: false, strikeThrough: false,
            insertUnorderedList: false, insertOrderedList: false, todo: false,
            justifyLeft: true, justifyCenter: false, justifyRight: false, justifyFull: false,
        },
        activeDropdown: null,
        currentTextColor: '#000000',
        currentHighlightColor: 'transparent',
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
        lastSaveTime: 0,

        init() {
            this.$nextTick(() => {
                this.initEditorElement();
            });
            window.addEventListener('refresh-editor-content', (e) => {
                if (this.saveTimeout) clearTimeout(this.saveTimeout);
                const cursor = e.detail ? (e.detail.cursor !== undefined ? e.detail.cursor : (Array.isArray(e.detail) && e.detail[0] && e.detail[0].cursor !== undefined ? e.detail[0].cursor : null)) : null;
                this.initEditorElement(cursor);
            });
            window.addEventListener('request-undo', () => {
                if (this.saveTimeout) clearTimeout(this.saveTimeout);
                const editorEl = document.getElementById(this.editorId);
                const html = editorEl ? editorEl.innerHTML : null;
                const offset = editorEl ? this.getCursorCharacterOffset(editorEl) : null;
                this.$wire.undoWithCurrentBody(html, offset);
            });
            window.addEventListener('request-redo', () => {
                if (this.saveTimeout) clearTimeout(this.saveTimeout);
                const editorEl = document.getElementById(this.editorId);
                const html = editorEl ? editorEl.innerHTML : null;
                const offset = editorEl ? this.getCursorCharacterOffset(editorEl) : null;
                this.$wire.redoWithCurrentBody(html, offset);
            });
        },

        initEditorElement(targetCursor = null) {
            if (this.saveTimeout) clearTimeout(this.saveTimeout);
            const editorEl = document.getElementById(this.editorId);
            if (!editorEl) return;

            document.execCommand('defaultParagraphSeparator', false, 'p');

            let initialBody = this.$wire[this.contentProp] || '';

            if (initialBody.trim() === '') {
                initialBody = '<p><br></p>';
            }
            editorEl.innerHTML = initialBody;
            this.updateCounter();
            this.refreshToolbarState();
            this.lastSaveTime = Date.now();

            if (targetCursor !== null && targetCursor !== undefined) {
                this.$nextTick(() => {
                    editorEl.focus();
                    this.restoreCursorToOffset(editorEl, targetCursor);
                });
            }

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
                if (e.key !== 'Tab') {
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
            const editorEl = document.getElementById(this.editorId);
            const sel = window.getSelection();
            if (sel && sel.rangeCount > 0 && editorEl && editorEl.contains(sel.anchorNode)) {
                this.savedRange = sel.getRangeAt(0).cloneRange();
            }
        },

        restoreSelection() {
            const editorEl = document.getElementById(this.editorId);
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
            const editorEl = document.getElementById(this.editorId);
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

            if (cmd === 'insertUnorderedList' || cmd === 'insertOrderedList') {
                this.toggleCustomList(cmd === 'insertUnorderedList' ? 'ul' : 'ol');
                return;
            }

            document.execCommand(cmd, false, val);
            this.refreshToolbarState();
            this.scheduleSave();
        },

        toggleCustomList(listType) {
            const editorEl = document.getElementById(this.editorId);
            if (!editorEl) return;
            const sel = window.getSelection();
            if (!sel || !sel.anchorNode) return;

            let node = sel.anchorNode;
            if (node.nodeType === 3) node = node.parentElement;

            const existingLi = node.closest('li');
            if (existingLi && editorEl.contains(existingLi)) {
                const parentList = existingLi.parentNode;
                if (parentList && parentList.tagName && parentList.tagName.toLowerCase() === listType) {
                    const p = document.createElement('p');
                    p.innerHTML = existingLi.innerHTML || '<br>';

                    if (parentList.children.length === 1) {
                        parentList.parentNode.replaceChild(p, parentList);
                    } else if (parentList.firstElementChild === existingLi) {
                        parentList.parentNode.insertBefore(p, parentList);
                        parentList.removeChild(existingLi);
                    } else if (parentList.lastElementChild === existingLi) {
                        parentList.parentNode.insertBefore(p, parentList.nextSibling);
                        parentList.removeChild(existingLi);
                    } else {
                        const newList = document.createElement(listType);
                        while (existingLi.nextElementSibling) {
                            newList.appendChild(existingLi.nextElementSibling);
                        }
                        parentList.parentNode.insertBefore(p, parentList.nextSibling);
                        parentList.parentNode.insertBefore(newList, p.nextSibling);
                        parentList.removeChild(existingLi);
                    }

                    const range = document.createRange();
                    range.selectNodeContents(p);
                    range.collapse(false);
                    sel.removeAllRanges();
                    sel.addRange(range);
                    this.refreshToolbarState();
                    this.scheduleSave();
                    return;
                } else if (parentList && ['ul', 'ol'].includes(parentList.tagName.toLowerCase())) {
                    if (parentList.children.length === 1) {
                        const newList = document.createElement(listType);
                        newList.appendChild(existingLi);
                        parentList.parentNode.replaceChild(newList, parentList);
                    } else {
                        const newList = document.createElement(listType);
                        newList.appendChild(existingLi);
                        parentList.parentNode.insertBefore(newList, parentList.nextSibling);
                    }
                    const range = document.createRange();
                    range.selectNodeContents(existingLi);
                    range.collapse(false);
                    sel.removeAllRanges();
                    sel.addRange(range);
                    this.refreshToolbarState();
                    this.scheduleSave();
                    return;
                }
            }

            let block = node.closest('.todo-item, p, h1, h2, h3, blockquote, div');
            if (!block || block === editorEl || !editorEl.contains(block)) {
                if (editorEl.contains(node) && node !== editorEl && node.parentNode === editorEl) {
                    block = node;
                } else {
                    return;
                }
            }

            let contentHtml = '<br>';
            if (block.classList && block.classList.contains('todo-item')) {
                const textSpan = block.querySelector('.todo-text');
                contentHtml = textSpan ? textSpan.innerHTML : (block.textContent || '<br>');
            } else {
                contentHtml = block.innerHTML || (block.textContent || '<br>');
            }
            if (!contentHtml.trim() || contentHtml.trim() === '<br>') contentHtml = '<br>';

            const li = document.createElement('li');
            li.innerHTML = contentHtml;

            const prev = block.previousElementSibling;
            const next = block.nextElementSibling;

            if (prev && prev.tagName && prev.tagName.toLowerCase() === listType) {
                prev.appendChild(li);
                block.parentNode.removeChild(block);
            } else if (next && next.tagName && next.tagName.toLowerCase() === listType) {
                next.insertBefore(li, next.firstElementChild);
                block.parentNode.removeChild(block);
            } else {
                const newList = document.createElement(listType);
                newList.appendChild(li);
                block.parentNode.replaceChild(newList, block);
            }

            const range = document.createRange();
            range.selectNodeContents(li);
            range.collapse(false);
            sel.removeAllRanges();
            sel.addRange(range);
            this.refreshToolbarState();
            this.scheduleSave();
        },

        setFormat(tag, label, targetBlock = null) {
            const editorEl = document.getElementById(this.editorId);
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

            if (!block || block === editorEl) {
                let targetNode = sel && sel.anchorNode ? sel.anchorNode : null;
                if (targetNode && targetNode !== editorEl && editorEl && editorEl.contains(targetNode)) {
                    while (targetNode.parentElement && targetNode.parentElement !== editorEl) {
                        targetNode = targetNode.parentElement;
                    }
                    const newBlock = document.createElement(tag === 'p' ? 'p' : tag);
                    targetNode.parentNode.insertBefore(newBlock, targetNode);
                    newBlock.appendChild(targetNode);
                    block = newBlock;
                } else if (editorEl && editorEl.firstChild) {
                    const newBlock = document.createElement(tag === 'p' ? 'p' : tag);
                    while (editorEl.firstChild) {
                        newBlock.appendChild(editorEl.firstChild);
                    }
                    editorEl.appendChild(newBlock);
                    block = newBlock;
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
                if (!newBlock.textContent.replace(/^[\s\u200B\uFEFF]+|[\s\u200B\uFEFF]+$/g, '') || newBlock.textContent.trim() === '') {
                    newBlock.innerHTML = '<br>';
                } else if (tag === 'h1' || tag === 'h2' || tag === 'h3' || tag === 'p' || tag === 'blockquote') {
                    newBlock.querySelectorAll('font, span, strong, b, em, i, u, s').forEach(el => {
                        if (el.style) {
                            el.removeAttribute('style');
                        }
                        if (el.removeAttribute) {
                            el.removeAttribute('size');
                            el.removeAttribute('face');
                            el.removeAttribute('color');
                        }
                        while (el.firstChild) {
                            el.parentNode.insertBefore(el.firstChild, el);
                        }
                        el.parentNode.removeChild(el);
                    });
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
                        newRange.setStart(newBlock, 0);
                        newRange.collapse(true);
                    }
                    if (sel) {
                        sel.removeAllRanges();
                        sel.addRange(newRange);
                    }
                } catch (e) {
                    try {
                        const fallbackRange = document.createRange();
                        fallbackRange.setStart(newBlock, 0);
                        fallbackRange.collapse(true);
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
            const editorEl = document.getElementById(this.editorId);
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
                        if (this.activeStates.bold && ['h1', 'h2', 'h3'].includes(block.tagName.toLowerCase())) {
                            let el = sel.anchorNode;
                            if (el && el.nodeType === 3) el = el.parentElement;
                            let explicitBold = false;
                            while (el && el !== block && el !== editorEl) {
                                if (el.tagName && ['B', 'STRONG'].includes(el.tagName.toUpperCase())) {
                                    explicitBold = true;
                                    break;
                                }
                                if (el.style && (el.style.fontWeight === 'bold' || parseInt(el.style.fontWeight, 10) >= 600)) {
                                    explicitBold = true;
                                    break;
                                }
                                el = el.parentElement;
                            }
                            this.activeStates.bold = explicitBold;
                        }

                        if (block.classList.contains('todo-item') || node.closest('.todo-item')) {
                            this.activeStates.todo = true;
                            this.activeStates.insertUnorderedList = false;
                        }

                        const tag = block.tagName.toLowerCase();
                        const map = { 
                            p: this.i18n ? this.i18n.normalText : 'Normal Text', 
                            h1: this.i18n ? this.i18n.heading1 : 'Heading 1', 
                            h2: this.i18n ? this.i18n.heading2 : 'Heading 2', 
                            h3: this.i18n ? this.i18n.heading3 : 'Heading 3', 
                            blockquote: this.i18n ? this.i18n.quote : 'Quote' 
                        };
                        if (map[tag]) {
                            this.formatValue = tag;
                            this.formatLabel = map[tag];
                        } else {
                            this.formatValue = 'p';
                            this.formatLabel = this.i18n ? this.i18n.normalText : 'Normal Text';
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
                    } else {
                        this.formatValue = 'p';
                        this.formatLabel = this.i18n ? this.i18n.normalText : 'Normal Text';
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
            const editorEl = document.getElementById(this.editorId);
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
            checkbox.className = 'todo-checkbox mt-1 cursor-pointer w-4 h-4 rounded border-secondary-100 text-secondary-200 accent-secondary-200 focus:ring-secondary-200 shrink-0';
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
            if (block.tagName && block.tagName.toLowerCase() === 'li' && block.parentNode && ['ul', 'ol'].includes(block.parentNode.tagName.toLowerCase())) {
                const parentList = block.parentNode;
                if (parentList.children.length === 1) {
                    parentList.parentNode.replaceChild(todoDiv, parentList);
                } else if (parentList.firstElementChild === block) {
                    parentList.parentNode.insertBefore(todoDiv, parentList);
                    parentList.removeChild(block);
                } else {
                    parentList.parentNode.insertBefore(todoDiv, parentList.nextSibling);
                    parentList.removeChild(block);
                }
            } else {
                block.parentNode.replaceChild(todoDiv, block);
            }

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
            const editorEl = document.getElementById(this.editorId);
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
                    const trimmed = textBefore.replace(/^[\s\u00A0\u200B\uFEFF]+|[\s\u00A0\u200B\uFEFF]+$/g, '');

                    if (hasTrailingSpace && trimmed === '#') {
                        this.deleteShortcutPrefix(node, sel.anchorOffset);
                        this.setFormat('h1', this.i18n ? this.i18n.heading1 : 'Heading 1', block);
                        return;
                    }
                    if (hasTrailingSpace && trimmed === '##') {
                        this.deleteShortcutPrefix(node, sel.anchorOffset);
                        this.setFormat('h2', this.i18n ? this.i18n.heading2 : 'Heading 2', block);
                        return;
                    }
                    if (hasTrailingSpace && trimmed === '###') {
                        this.deleteShortcutPrefix(node, sel.anchorOffset);
                        this.setFormat('h3', this.i18n ? this.i18n.heading3 : 'Heading 3', block);
                        return;
                    }
                    if (hasTrailingSpace && (trimmed === '>' || trimmed === '{}' || trimmed === '&gt;')) {
                        this.deleteShortcutPrefix(node, sel.anchorOffset);
                        this.setFormat('blockquote', this.i18n ? this.i18n.quote : 'Quote', block);
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

        customIndent(outdent = false) {
            const editorEl = document.getElementById(this.editorId);
            const sel = window.getSelection();
            let node = sel && sel.anchorNode ? sel.anchorNode : null;
            if (node && node.nodeType === 3) node = node.parentElement;
            
            const isList = node && node.closest('li, ul, ol, .todo-item');
            if (isList) {
                if (outdent) {
                    this.exec('outdent');
                } else {
                    this.exec('indent');
                }
            } else {
                const block = node ? node.closest('p, h1, h2, h3, div') : null;
                if (block && editorEl && editorEl.contains(block)) {
                    let currentIndent = parseInt(block.style.marginLeft || block.style.paddingLeft || '0', 10);
                    if (isNaN(currentIndent)) currentIndent = 0;
                    if (outdent) {
                        currentIndent = Math.max(0, currentIndent - 40);
                    } else {
                        currentIndent += 40;
                    }
                    if (currentIndent > 0) {
                        block.style.marginLeft = currentIndent + 'px';
                    } else {
                        block.style.marginLeft = '';
                        block.style.paddingLeft = '';
                    }
                    this.scheduleSave();
                    this.updateActiveFormats();
                } else {
                    if (outdent) {
                        this.exec('outdent');
                    } else {
                        this.exec('indent');
                    }
                }
            }
        },

        handleKeydown(e) {
            const editorEl = document.getElementById(this.editorId);
            if (e.key === 'Tab') {
                e.preventDefault();
                this.customIndent(e.shiftKey);
                return;
            }

            if (e.key === 'Backspace') {
                const sel = window.getSelection();
                if (sel && sel.anchorNode) {
                    let node = sel.anchorNode;
                    if (node.nodeType === 3) node = node.parentElement;
                    
                    const block = node.closest('p, h1, h2, h3, div, li, .todo-item');
                    if (block && editorEl.contains(block)) {
                        let isAtStart = false;
                        if (sel.isCollapsed && sel.anchorOffset === 0) {
                            try {
                                const rangeBefore = document.createRange();
                                rangeBefore.setStart(block, 0);
                                rangeBefore.setEnd(sel.anchorNode, sel.anchorOffset);
                                if (rangeBefore.toString().replace(/^[\s\u200B\uFEFF]+|[\s\u200B\uFEFF]+$/g, '') === '') {
                                    isAtStart = true;
                                }
                            } catch (err) {}
                        }
                        
                        let currentIndent = parseInt(block.style.marginLeft || block.style.paddingLeft || '0', 10);
                        if (!isNaN(currentIndent) && currentIndent > 0 && (block.textContent.replace(/^[\s\u200B\uFEFF]+|[\s\u200B\uFEFF]+$/g, '') === '' || isAtStart)) {
                            e.preventDefault();
                            this.customIndent(true);
                            return;
                        }
                    }

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
                            this.refreshToolbarState();
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
                            this.setFormat('p', this.i18n ? this.i18n.normalText : 'Normal Text', formatBlock);
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
                            const textSpan = todoItem.querySelector('.todo-text');
                            let afterHtml = '<br>';
                            if (textSpan && sel && sel.rangeCount > 0) {
                                try {
                                    const rangeAfter = document.createRange();
                                    rangeAfter.setStart(sel.anchorNode, sel.anchorOffset);
                                    rangeAfter.setEndAfter(textSpan.lastChild || textSpan);
                                    const extracted = rangeAfter.extractContents();
                                    const tmp = document.createElement('div');
                                    tmp.appendChild(extracted);
                                    if (tmp.innerHTML && tmp.textContent.trim() !== '') {
                                        afterHtml = tmp.innerHTML;
                                    }
                                    if (!textSpan.innerHTML || textSpan.textContent.trim() === '') {
                                        textSpan.innerHTML = '<br>';
                                    }
                                } catch (err) {
                                    afterHtml = '<br>';
                                }
                            }

                            const newTodo = document.createElement('div');
                            newTodo.className = 'todo-item flex items-start gap-2 my-1';

                            const checkbox = document.createElement('input');
                            checkbox.type = 'checkbox';
                            checkbox.className = 'todo-checkbox mt-1 cursor-pointer w-4 h-4 rounded border-secondary-100 text-secondary-200 accent-secondary-200 focus:ring-secondary-200 shrink-0';
                            checkbox.setAttribute('contenteditable', 'false');
                            const onClickCode = 'this.nextElementSibling.style.textDecoration = this.checked ? \'line-through\' : \'none\'; this.nextElementSibling.style.opacity = this.checked ? \'0.5\' : \'1\';';
                            checkbox.setAttribute('onclick', onClickCode);

                            const span = document.createElement('span');
                            span.className = 'todo-text flex-1 outline-none';
                            span.innerHTML = afterHtml;

                            newTodo.appendChild(checkbox);
                            newTodo.appendChild(span);

                            if (todoItem.nextSibling) {
                                todoItem.parentNode.insertBefore(newTodo, todoItem.nextSibling);
                            } else {
                                todoItem.parentNode.appendChild(newTodo);
                            }
                            const newSpan = newTodo.querySelector('.todo-text');
                            const range = document.createRange();
                            range.selectNodeContents(newSpan);
                            range.collapse(true);
                            sel.removeAllRanges();
                            sel.addRange(range);
                        }
                        this.scheduleSave();
                        this.refreshToolbarState();
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
                                    this.setFormat('p', this.i18n ? this.i18n.normalText : 'Normal Text');
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
            const now = Date.now();
            if (!this.lastSaveTime) {
                this.lastSaveTime = now;
            }
            if (now - this.lastSaveTime >= 1500) {
                if (this.saveTimeout) clearTimeout(this.saveTimeout);
                this.executeSave();
                return;
            }
            if (this.saveTimeout) clearTimeout(this.saveTimeout);
            this.saveTimeout = setTimeout(() => {
                this.executeSave();
            }, 500);
        },

        executeSave() {
            this.lastSaveTime = Date.now();
            const editorEl = document.getElementById(this.editorId);
            if (editorEl && typeof this.$wire[this.updateMethod] === 'function') {
                this.$wire[this.updateMethod](editorEl.innerHTML, this.getCursorCharacterOffset(editorEl));
            }
        },

        getCursorCharacterOffset(element) {
            if (!element) return null;
            const selection = window.getSelection();
            if (!selection || !selection.rangeCount) return null;
            const range = selection.getRangeAt(0);
            if (!element.contains(range.startContainer)) return null;

            try {
                const preCaretRange = range.cloneRange();
                preCaretRange.selectNodeContents(element);
                preCaretRange.setEnd(range.startContainer, range.startOffset);
                return preCaretRange.toString().length;
            } catch(e) {
                return null;
            }
        },

        restoreCursorToOffset(element, offset) {
            if (!element || offset === null || offset === undefined) return;
            const selection = window.getSelection();
            if (!selection) return;

            let charCount = 0;
            let nodeToSelect = null;
            let offsetInNode = 0;

            function traverse(node) {
                if (node.nodeType === Node.TEXT_NODE) {
                    const nextCharCount = charCount + node.length;
                    if (!nodeToSelect && offset <= nextCharCount) {
                        nodeToSelect = node;
                        offsetInNode = offset - charCount;
                    }
                    charCount = nextCharCount;
                } else {
                    for (let i = 0; i < node.childNodes.length; i++) {
                        traverse(node.childNodes[i]);
                        if (nodeToSelect) break;
                    }
                }
            }

            traverse(element);

            if (nodeToSelect) {
                try {
                    const range = document.createRange();
                    range.setStart(nodeToSelect, Math.max(0, Math.min(offsetInNode, nodeToSelect.length)));
                    range.collapse(true);
                    selection.removeAllRanges();
                    selection.addRange(range);
                } catch (e) {}
            } else if (element.childNodes.length > 0) {
                try {
                    const range = document.createRange();
                    range.selectNodeContents(element);
                    range.collapse(false);
                    selection.removeAllRanges();
                    selection.addRange(range);
                } catch (e) {}
            }
        },

        updateCounter() {
            const editorEl = document.getElementById(this.editorId);
            if (!editorEl) return;
            const text = (editorEl.innerText || '').replace(/[\u200B\u00A0]/g, ' ').replace(/\s+/g, ' ').trim();
            this.wordCount = text === '' ? 0 : text.split(/\s+/).filter(Boolean).length;
            this.charCount = text.length;
        },

        // Block Drag & Drop State
        onEditorMouseMove(e) {
            if (this.isDraggingBlock) return;
            const editor = document.getElementById(this.editorId);
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

            const editor = document.getElementById(this.editorId);
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

            const editor = document.getElementById(this.editorId);
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
            const editorEl = document.getElementById(this.editorId);
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
    }));
});
