                <div class="draft-tabs-container text-app-desc-feature text-text-70 shrink-0"
                     x-data="{
                         draggedDraftId: null,
                         dragOverDraftId: null
                     }"
                >
                    @foreach($drafts as $draft)
                        <div
                            class="draft-tab {{ $activeDraftId === $draft->manuscript_id ? 'active' : '' }}"
                            :class="{
                                'draft-drag-over': dragOverDraftId === '{{ $draft->manuscript_id }}'
                            }"
                            data-draft-id="{{ $draft->manuscript_id }}"
                            wire:click="selectDraft('{{ $draft->manuscript_id }}')"
                            draggable="true"
                            @dragstart="draggedDraftId = '{{ $draft->manuscript_id }}'; $event.dataTransfer.effectAllowed = 'move'; $event.dataTransfer.dropEffect = 'move';"
                            @dragover.prevent="if (draggedDraftId && draggedDraftId !== '{{ $draft->manuscript_id }}') dragOverDraftId = '{{ $draft->manuscript_id }}';"
                            @dragenter.prevent="if (draggedDraftId && draggedDraftId !== '{{ $draft->manuscript_id }}') dragOverDraftId = '{{ $draft->manuscript_id }}';"
                            @dragleave="if (dragOverDraftId === '{{ $draft->manuscript_id }}') dragOverDraftId = null;"
                            @drop.prevent="
                                if (draggedDraftId && draggedDraftId !== '{{ $draft->manuscript_id }}') {
                                    $wire.moveDraft(draggedDraftId, '{{ $draft->manuscript_id }}');
                                }
                                draggedDraftId = null;
                                dragOverDraftId = null;
                            "
                            @dragend="draggedDraftId = null; dragOverDraftId = null;"
                        >
                            {{-- Drag grip --}}
                            <span class="draft-drag" title="{{ __('Drag to reorder') }}">
                                <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor">
                                    <circle cx="8" cy="6" r="1.5"/><circle cx="16" cy="6" r="1.5"/>
                                    <circle cx="8" cy="12" r="1.5"/><circle cx="16" cy="12" r="1.5"/>
                                    <circle cx="8" cy="18" r="1.5"/><circle cx="16" cy="18" r="1.5"/>
                                </svg>
                            </span>

                            {{-- Title / Rename --}}
                            <span
                                x-show="renamingDraftId !== '{{ $draft->manuscript_id }}'"
                                @dblclick.stop="startRenameDraft('{{ $draft->manuscript_id }}', $el.innerText.trim())"
                                class="truncate max-w-[120px]"
                                title="{{ __('Double-click to rename') }}"
                            >{{ !empty(trim($draft->title ?? '')) ? \App\Helpers\TextHelper::localizeDefaultName($draft->title) : __('Draft') }}</span>

                            <input
                                x-show="renamingDraftId === '{{ $draft->manuscript_id }}'"
                                x-cloak
                                id="draft-rename-{{ $draft->manuscript_id }}"
                                x-model="renameValue"
                                @keydown.enter.stop="commitRenameDraft('{{ $draft->manuscript_id }}')"
                                @keydown.escape.stop="renamingDraftId = null"
                                @blur="commitRenameDraft('{{ $draft->manuscript_id }}')"
                                @click.stop
                                class="w-20 text-app-desc-feature text-text-70 bg-bg-main border border-brand-150 rounded px-1 py-0 outline-none"
                            />

                            {{-- Close/Delete --}}
                            @if($drafts->count() > 1)
                                <button
                                    class="draft-close"
                                    @click.stop="confirmDeleteDraft('{{ $draft->manuscript_id }}')"
                                    title="{{ __('Delete draft') }}"
                                >
                                    <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    @endforeach

                    {{-- Add Draft Button --}}
                    <button
                        wire:click="addDraft"
                        class="shrink-0 w-7 h-7 flex items-center justify-center text-secondary-50 hover:text-secondary-100 hover:bg-brand-50 rounded transition-colors ml-1 mt-0.5"
                        title="{{ __('Add new draft') }}"
                    >
                        <x-icons.add class="w-4 h-4"/>
                    </button>

                    {{-- Completes the bottom border of the editor box to the right --}}
                    <div class="flex-1 min-w-[20px] border-b-0 border-brand-150 self-start mt-0 shrink-0"></div>
                </div>
