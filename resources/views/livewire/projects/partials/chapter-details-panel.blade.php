            <div class="overflow-hidden transition-all duration-300 ease-in-out shrink-0 flex flex-col"
                 :class="showDetailPanel ? 'w-[280px] opacity-100' : 'w-0 opacity-0 pointer-events-none'"
            >
                <div class="w-[280px] h-full flex flex-col py-3 pl-3 bg-brand-50 border border-r-0 border-brand-150 rounded-l-xl shadow-sm z-10 shrink-0">
                    <div class="flex-1 flex flex-col gap-3 overflow-y-auto custom-scrollbar pr-3">
                {{-- Top Row: Status Badge + Dropdown + Hide Details Button (Right) --}}
                <div class="flex items-center justify-between relative pt-2" x-data="{ showStatusMenu: false }">
                    <div class="relative">
                        <button type="button" @click="showStatusMenu = !showStatusMenu" @class([
                            'w-full text-app-caption px-3 py-1.5 rounded-lg flex items-center justify-between gap-1.5 shadow-sm border border-brand-150 transition-all cursor-pointer hover:opacity-90',
                            'bg-warning-100/50 text-text-80' => $chapterCard->status === 'In Progress',
                            'bg-success-100/50 text-text-80' => $chapterCard->status === 'Completed',
                            'bg-text-100 text-text-80' => !in_array($chapterCard->status, ['In Progress', 'Completed'])
                        ])>
                            <div class="flex items-center gap-1.5 min-w-0">
                                <x-icons.chapter-status :status="$chapterCard->status" class="w-3.5 h-3.5 shrink-0" />
                                <span class="truncate">{{ $chapterCard->status ?? 'In Progress' }}</span>
                            </div>
                            <x-icons.chevron rotate="90" size="w-3 h-3" color="subtext-70"/>
                        </button>

                        <div x-show="showStatusMenu" @click.away="showStatusMenu = false" x-cloak
                             class="absolute left-0 top-full mt-1.5 w-full min-w-full bg-white rounded-lg shadow-lg border border-brand-150 py-1 z-50">
                            @foreach(['In Progress', 'Completed'] as $st)
                                @php
                                    $isSelected = $chapterCard->status === $st;
                                @endphp
                                <button
                                    type="button"
                                    @if(!$isSelected) wire:click="updateStatus('{{ $st }}')" @click="showStatusMenu = false" @endif
                                    @disabled($isSelected)
                                    @class([
                                        'w-full text-left px-3 py-2 text-app-caption flex items-center gap-2 transition-colors',
                                        'opacity-50 cursor-not-allowed bg-subtext-60/50 text-subtext-100' => $isSelected,
                                        'hover:bg-card-bg text-text-70 cursor-pointer' => !$isSelected,
                                    ])
                                >
                                    <x-icons.chapter-status :status="$st" class="w-3.5 h-3.5 shrink-0" />
                                    <span class="truncate">{{ $st }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="showDetailPanel = false"
                        class="flex items-center gap-1 px-1 py-1 rounded-md border border-secondary-150 bg-card-hover hover:bg-card-bg text-text-80 text-app-caption transition-all shadow-sm"
                        title="Hide Chapter Details"
                    >
                        <x-icons.chevron size="w-3 h-3" rotate="180"/>
                    </button>
                </div>

                {{-- Cover Image --}}
                <div class="pb-1">
                    <div x-data="{ 
                            hoverCover: false,
                            isUploading: false, 
                            progress: 0, 
                            clientError: null,
                            showCropper: false,
                            cropImageUrl: null,
                            cropperInstance: null,
                            
                            cancelCrop() {
                                this.showCropper = false;
                                if (this.cropperInstance) {
                                    this.cropperInstance.destroy();
                                    this.cropperInstance = null;
                                }
                                this.cropImageUrl = null;
                                if(this.$refs.coverInput) this.$refs.coverInput.value = null;
                            },
                            
                            onFileChange(e) {
                                const file = e.target.files[0];
                                if (!file) return;
                                
                                if (file.size > 5 * 1024 * 1024) {
                                    this.clientError = 'The selected image is too large. The maximum allowed file size is 5MB.';
                                    if(this.$refs.coverInput) this.$refs.coverInput.value = '';
                                    return;
                                }
                                
                                this.clientError = null;
                                
                                const reader = new FileReader();
                                reader.onload = (event) => {
                                    this.cropImageUrl = event.target.result;
                                    this.showCropper = true;
                                    
                                    this.$nextTick(() => {
                                        const image = this.$refs.cropImage;
                                        this.cropperInstance = new Cropper(image, {
                                            aspectRatio: 16 / 8,
                                            viewMode: 3,
                                            autoCropArea: 1,
                                            dragMode: 'move',
                                            modal: false,
                                            background: false,
                                            guides: false,
                                            center: true,
                                            highlight: false,
                                            cropBoxMovable: false,
                                            cropBoxResizable: false,
                                        });
                                    });
                                };
                                reader.readAsDataURL(file);
                            },
                            
                            applyCrop() {
                                if (!this.cropperInstance) return;
                                
                                const canvas = this.cropperInstance.getCroppedCanvas({
                                    width: 1280,
                                    height: 640,
                                    imageSmoothingEnabled: true,
                                    imageSmoothingQuality: 'high',
                                });
                                
                                canvas.toBlob((blob) => {
                                    const file = new File([blob], 'chapter-cover.jpg', { type: 'image/jpeg', lastModified: Date.now() });
                                    
                                    this.cancelCrop();
                                    
                                    this.isUploading = true;
                                    this.progress = 0;
                                    
                                    @this.upload('coverUpload', file,
                                        (uploadedFilename) => { this.isUploading = false; },
                                        () => { this.isUploading = false; },
                                        (e) => { this.progress = e.detail.progress; }
                                    );
                                }, 'image/jpeg', 0.9);
                            }
                        }"
                        @mouseover="hoverCover = true" @mouseleave="hoverCover = false"
                        class="relative w-full aspect-[16/8] rounded-xl overflow-hidden bg-brand-50 border border-brand-150 shadow-inner group shrink-0 flex items-center justify-center">

                        <!-- Progress Overlay -->
                        <div x-show="isUploading" x-transition class="absolute inset-0 bg-[#F5EFE9]/80 backdrop-blur-md z-40 flex flex-col items-center justify-center">
                            <svg class="animate-spin h-6 w-6 text-secondary-200 mb-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <div class="text-secondary-200 font-semibold text-xs">Uploading... <span x-text="progress + '%'"></span></div>
                            
                            <div class="w-1/2 bg-brand-150 rounded-full h-1.5 mt-2 overflow-hidden shadow-inner mx-auto">
                                <div class="bg-secondary-100 h-full rounded-full transition-all duration-200 ease-out" :style="`width: ${progress}%`"></div>
                            </div>
                        </div>

                        @if($chapterCard->cover_image_path)
                            <img src="{{ Storage::url($chapterCard->cover_image_path) }}" class="w-full h-full object-cover transition-transform duration-300" alt="Chapter Cover">
                        @elseif($project->cover_image_path)
                            <img src="{{ Storage::url($project->cover_image_path) }}" class="w-full h-full object-cover transition-transform duration-300" alt="Project Cover">
                        @else
                            <div class="flex flex-col items-center justify-center gap-1 p-4 text-center">
                                <x-icons.no-structure class="w-8 h-8 text-secondary-150 opacity-60" />
                                <span class="text-app-caption text-secondary-150">No cover image</span>
                            </div>
                        @endif

                        {{-- Hover Action Buttons for Chapter Cover--}}
                        <div x-show="hoverCover && !showCropper" x-transition class="absolute bottom-2.5 left-2.5 z-30 flex items-center gap-1.5">
                            <label class="flex items-center gap-1.5 px-2.5 py-1.5 bg-text-80/95 border border-text-60 hover:bg-text-80 text-bg-main text-app-caption text-[9px] rounded-md shadow-lg cursor-pointer transition-transform active:scale-95">
                                <x-icons.upload class="w-3 h-3 text-bg-main" />
                                <span>{{ $chapterCard->cover_image_path || $project->cover_image_path ? 'Change Cover' : 'Upload Cover' }}</span>
                                <input type="file" x-ref="coverInput" @change="onFileChange" accept="image/*" class="hidden">
                            </label>

                            @if($chapterCard->cover_image_path || $project->cover_image_path)
                                <button type="button" wire:click="detachCoverImage" class="flex items-center gap-1.5 px-2.5 py-1.5 bg-text-80/95 border border-text-60 hover:bg-text-80 text-danger-100 text-app-caption text-[9px] rounded-md shadow-lg cursor-pointer transition-transform active:scale-95" title="Detach Cover">
                                    <x-icons.delete class="w-3 h-3 text-danger-100" />
                                    <span>Remove</span>
                                </button>
                            @endif
                        </div>

                        {{-- Client-side Error --}}
                        <template x-if="clientError">
                            <div class="absolute inset-x-2 top-2 bg-danger-100/95 text-bg-main text-[11px] font-medium px-2.5 py-2 rounded shadow-xl z-50 flex items-start gap-1.5">
                                <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-bg-main" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <span x-text="clientError" class="flex-1 leading-relaxed"></span>
                                <button type="button" @click.stop="clientError = null" class="shrink-0 ml-1 p-0.5 hover:bg-black/20 rounded transition-colors" title="Dismiss"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                            </div> 
                        </template>

                        {{-- Server-side Error --}}
                        @error('coverUpload') 
                            <div x-data="{ show: true }" x-show="show" class="absolute inset-x-2 top-2 bg-danger-100/95 text-bg-main text-[11px] font-medium px-2.5 py-2 rounded shadow-xl z-50 flex items-start gap-1.5">
                                <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-bg-main" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <span class="flex-1 leading-relaxed">{{ $message }}</span>
                                <button type="button" @click.stop="show = false" class="shrink-0 ml-1 p-0.5 hover:bg-black/20 rounded transition-colors" title="Dismiss"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                            </div> 
                        @enderror

                        {{-- Inline Cropper UI --}}
                        <div x-show="showCropper" style="display: none;" class="absolute inset-0 z-40 bg-brand-50 flex flex-col" wire:ignore @dragstart.prevent @drop.prevent>
                            <style>
                                .cropper-view-box { outline: none !important; }
                                .cropper-modal { background: none !important; opacity: 0 !important; }
                            </style>
                            <div class="absolute inset-0 w-full h-full bg-black overflow-hidden">
                                <img x-ref="cropImage" :src="cropImageUrl" draggable="false" class="block w-full h-full select-none" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                            </div>
                            <div class="absolute bottom-2.5 left-0 right-0 flex justify-center gap-2 z-50">
                                <button @click.stop="cancelCrop()" type="button" class="px-3 py-1 bg-bg-main/90 backdrop-blur text-text-70 text-[10px] font-bold uppercase tracking-wider rounded-md border border-text-60 hover:bg-bg-main shadow-lg transition-colors cursor-pointer">Cancel</button>
                                <button @click.stop="applyCrop()" type="button" class="px-3 py-1 bg-secondary-100/95 backdrop-blur text-bg-main text-[10px] font-bold uppercase tracking-wider rounded-md shadow-lg border border-secondary-200 hover:bg-secondary-200 transition-colors cursor-pointer">Save</button>
                            </div>
                        </div>

                        <div wire:loading.flex wire:target="coverUpload" class="absolute inset-0 bg-text-70/80 backdrop-blur-[1px] items-center justify-center gap-2 text-app-desc-feature z-40">
                            <svg class="animate-spin h-5 w-5 text-bg-main" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span class="text-bg-main">Uploading Cover...</span>
                        </div>
                    </div>
                </div>

                {{-- 2. Chapter Info & Title Rename --}}
                <div x-data="{
                    editingTitle: false,
                    titleVal: '{{ addslashes($chapterCard->title) }}',
                    startEdit() {
                        this.editingTitle = true;
                        if (this.$refs.titleDisplay) {
                            this.titleVal = this.$refs.titleDisplay.innerText.trim();
                        }
                        this.$nextTick(() => { $refs.titleInput.focus(); $refs.titleInput.select(); });
                    },
                    saveTitle() {
                        if (this.titleVal.trim() !== '') {
                            $wire.renameChapter(this.titleVal);
                        }
                        this.editingTitle = false;
                    }
                }" class="border-b border-secondary-50/50 pb-3.5">
                    <p class="text-app-caption font-medium text-secondary-200 mb-1">Chapter {{ $chapterCard->order_index }}</p>

                    {{-- Display Title --}}
                    <div x-show="!editingTitle" class="group flex items-start justify-between gap-2 mb-1.5">
                        <h2 x-ref="titleDisplay" @dblclick="startEdit"
                            class="text-web-heading-2 text-[24px] text-text-80 leading-snug line-clamp-2 cursor-pointer hover:text-secondary-200 transition-colors"
                            title="Double-click to rename chapter"
                        >
                            {{ $chapterCard->title }}
                        </h2>
                        <button type="button" @click="startEdit"
                                class="p-1 rounded hover:bg-brand-150 text-secondary-150 opacity-70 group-hover:opacity-100 transition-opacity shrink-0 cursor-pointer"
                                title="Rename Chapter">
                            <x-icons.rename/>
                        </button>
                    </div>

                    {{-- Inline Input Title --}}
                    <div x-show="editingTitle" x-cloak class="mb-1.5">
                        <input type="text" x-ref="titleInput" x-model="titleVal"
                               @keydown.enter="saveTitle" @keydown.escape="editingTitle = false" @blur="saveTitle"
                               class="w-full text-web-heading-2 text-text-70 bg-bg-main border border-secondary-100 rounded px-2 py-1 shadow-sm focus:outline-none focus:ring-2 focus:ring-secondary-150/30">
                        <p class="text-app-caption text-subtext-90 mt-0.5">Press Enter to save, Esc to cancel</p>
                    </div>

                    <p class="text-app-caption text-secondary-100">
                        Last Edited: {{ $chapterCard->updated_at?->timezone('Asia/Jakarta')->format('d F Y, H.i') ?? '-' }}
                    </p>
                </div>

                {{-- 3. Collapsible Section: Summary --}}
                <div x-data="{
                    openSummary: true,
                    editingSummary: false,
                    summaryVal: '',
                    startEditSummary() {
                        this.editingSummary = true;
                        this.summaryVal = $refs.summaryDisplay.dataset.raw || '';
                        this.$nextTick(() => { $refs.summaryInput.focus(); });
                    },
                    saveSummary() {
                        $wire.updateSummary(this.summaryVal);
                        this.editingSummary = false;
                    }
                }" class="border-b border-secondary-50/50 pb-3.5">
                    <div class="flex items-center justify-between py-1">
                        <button type="button" @click="openSummary = !openSummary" class="flex items-center gap-1.5 text-left group cursor-pointer flex-1">
                            <svg class="w-3.5 h-3.5 text-secondary-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span class="text-app-desc-feature font-bold text-text-80 group-hover:text-secondary-200 transition-colors">Summary</span>
                        </button>
                        <div class="flex items-center gap-2.5">
                            <button type="button" @click="if(!openSummary) openSummary = true; startEditSummary()" class="text-app-caption text-secondary-150 hover:underline font-semibold cursor-pointer">Edit</button>
                            <button type="button" @click="openSummary = !openSummary" class="text-secondary-150 transition-transform duration-200 cursor-pointer p-0.5" :class="openSummary ? 'rotate-180' : ''">
                                <x-icons.chevron rotate="90" size="w-3 h-3" color="text-secondary-150"/>
                            </button>
                        </div>
                    </div>

                    <div x-show="openSummary" x-cloak x-transition class="pt-2">
                        <div x-show="!editingSummary">
                            <div x-ref="summaryDisplay" data-raw="{{ $displaySummary === 'No summary available for this chapter yet.' ? '' : $displaySummary }}"
                                 @dblclick="startEditSummary"
                                 style="word-break: break-word; overflow-wrap: break-word;"
                                 class="p-2.5 rounded-lg border border-transparent hover:border-secondary-100 hover:bg-brand-150/40 transition-all cursor-pointer text-app-body-small text-text-70 break-words whitespace-normal overflow-x-hidden"
                                 title="Double click to edit summary">
                                {{ $displaySummary }}
                            </div>
                        </div>

                        {{-- Edit Summary Textarea --}}
                        <div x-show="editingSummary" x-cloak class="flex flex-col gap-1.5 mt-1">
                            <textarea x-ref="summaryInput" x-model="summaryVal" rows="4"
                                      style="word-break: break-word; overflow-wrap: break-word;"
                                      class="w-full text-app-body-small text-text-70 bg-bg-main border border-secondary-100 rounded-lg p-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-secondary-150/30 custom-scrollbar break-words whitespace-normal overflow-x-hidden placeholder:text-subtext-80"
                                      placeholder="Write chapter summary here..."></textarea>
                            <div class="flex items-center justify-end gap-2 group">
                                <button type="button" @click="editingSummary = false" class="px-2 py-1 text-app-desc-feature text-secondary-150 group-hover:bg-brand-100 rounded cursor-pointer">Cancel</button>
                                <button type="button" @click="saveSummary" class="px-2.5 py-1 text-app-desc-feature bg-secondary-100 hover:bg-secondary-150 text-bg-main rounded shadow-sm cursor-pointer">Save</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. Collapsible Section: Tags --}}
                <div x-data="{ openTags: true, addingTag: false, tagVal: '' }" class="border-b border-secondary-50/50 pb-3.5">
                    <div class="flex items-center justify-between py-1">
                        <button type="button" @click="openTags = !openTags" class="flex items-center gap-1.5 text-left group cursor-pointer flex-1">
                            <x-icons.category class="w-3 h-3 text-secondary-200"/>
                            <span class="text-app-desc-feature font-bold text-text-80 group-hover:text-secondary-200 transition-colors">Tags ({{ $chapterCard->tags->count() }})</span>
                        </button>
                        <button type="button" @click="openTags = !openTags" class="text-secondary-150 transition-transform duration-200 cursor-pointer p-0.5" :class="openTags ? 'rotate-180' : ''">
                            <x-icons.chevron rotate="90" size="w-3 h-3" color="text-secondary-150"/>
                        </button>
                    </div>

                    <div x-show="openTags" x-cloak x-transition class="pt-2">
                        <div class="flex flex-wrap items-center gap-1.5 p-0.5">
                            {{-- Add Tag Button (inline when not adding) --}}
                            <button x-show="!addingTag" @click="addingTag = true; $nextTick(() => { $refs.tagInput.focus(); })"
                                    type="button"
                                    class="px-2.5 py-1 rounded-md border border-dashed border-secondary-150 bg-bg-main text-secondary-150 text-app-body-small text-[11px] font-medium hover:bg-bg-hover transition-colors cursor-pointer">
                                + Add Tag
                            </button>

                            {{-- Full Width Compact Add Tag Input Box (when adding, takes full line so tags drop below) --}}
                            <div x-show="addingTag" x-cloak class="w-full p-1.5 rounded-lg bg-secondary-100/50 border border-secondary-50/50 shadow-2xs flex flex-col gap-0.5 mb-1.5">
                                <div class="flex items-center justify-between gap-1">
                                    <input type="text" x-ref="tagInput" x-model="tagVal"
                                           @input="if(tagVal.length > 20) tagVal = tagVal.substring(0, 20)"
                                           @keydown.enter="if(tagVal.trim() !== '') { $wire.addTag(tagVal); tagVal = ''; addingTag = false; }"
                                           @keydown.escape="addingTag = false; tagVal = ''"
                                           placeholder="Tag..."
                                           maxlength="20"
                                           class="w-full bg-transparent border-0 border-b border-secondary-200 px-0.5 py-0.5 text-app-body-small text-[11.5px] text-subtext-90 placeholder-text-80/60 focus:outline-none focus:ring-0 focus:border-secondary-200 font-medium leading-tight">
                                    <button type="button" @click="addingTag = false; tagVal = ''" class="text-secondary-150 hover:text-secondary-200 font-bold text-xs px-1 leading-none transition-colors cursor-pointer" title="Cancel">&times;</button>
                                </div>
                                <div class="flex justify-end">
                                    <span class="text-app-caption text-[9px] font-semibold text-secondary-150 leading-none" x-text="tagVal.length + '/20'">0/20</span>
                                </div>
                            </div>

                            @if($chapterCard->tags && $chapterCard->tags->isNotEmpty())
                                @foreach($chapterCard->tags as $tag)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-brand-150 text-[11.5px] text-app-body-small text-secondary-200 font-semibold border border-brand-200 max-w-[140px] shadow-2xs group"
                                          title="{{ $tag->name }}">
                                        <span class="truncate">{{ $tag->name }}</span>
                                        <button type="button" wire:click="removeTag({{ $tag->id }})" class="text-secondary-150 hover:text-secondary-200 opacity-60 group-hover:opacity-100 transition-opacity ml-0.5 cursor-pointer" title="Remove tag">&times;</button>
                                    </span>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                {{-- 5. Collapsible Section: Characters --}}
                <div x-data="{ openChar: true, showCharDropdown: false }" class="pb-2">
                    <div class="flex items-center justify-between py-1">
                        <button type="button" @click="openChar = !openChar" class="flex items-center gap-1.5 text-left group cursor-pointer flex-1">
                            <svg class="w-3.5 h-3.5 text-secondary-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span class="text-app-desc-feature font-bold text-text-80 group-hover:text-secondary-200 transition-colors">Characters ({{ $chapterCard->characters->count() }})</span>
                        </button>
                        <button type="button" @click="openChar = !openChar" class="transition-transform duration-200 cursor-pointer p-0.5" :class="openChar ? 'rotate-180' : ''">
                            <x-icons.chevron rotate="90" size="w-3 h-3" color="text-secondary-150"/>
                        </button>
                    </div>

                    <div x-show="openChar" x-cloak x-transition class="pt-2">
                        <div class="relative">
                            <button type="button" @click="showCharDropdown = !showCharDropdown"
                                    class="w-full flex items-center justify-center gap-2 px-3.5 py-2 rounded-lg border border-dashed border-secondary-150 bg-bg-main text-app-desc-feature text-secondary-150 hover:bg-white transition-colors shadow-sm cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>Add Character...</span>
                            </button>

                            <div x-show="showCharDropdown" @click.away="showCharDropdown = false" x-cloak
                                 class="absolute left-0 top-full mt-1.5 w-full bg-bg-main rounded-lg shadow-xl border border-brand-150 max-h-[200px] overflow-y-auto info-panel-scrollbar z-50 py-1">
                                @if(empty($projectCharacters) || $projectCharacters->isEmpty())
                                    <div class="px-3 py-3 text-center text-app-captiontext-secondary-50">
                                        No characters found in this project yet.
                                    </div>
                                @else
                                    @php
                                        $addedCharIds = $chapterCard->characters->pluck('character_id')->toArray();
                                    @endphp
                                    @foreach($projectCharacters as $pChar)
                                        @php
                                            $isCharAdded = in_array($pChar->character_id, $addedCharIds);
                                        @endphp
                                        <button
                                            type="button"
                                            @if(!$isCharAdded) wire:click="attachCharacter('{{ $pChar->character_id }}')" @click="showCharDropdown = false" @endif
                                            @class([
                                                'w-full flex items-center gap-2.5 px-3 py-2 text-left text-[12px] font-app-body-small font-medium transition-colors',
                                                'opacity-50 cursor-not-allowed bg-bg-main' => $isCharAdded,
                                                'hover:bg-brand-50 text-text-70 cursor-pointer' => !$isCharAdded,
                                            ])
                                        >
                                            @if($pChar->image_path)
                                                <img src="{{ Storage::url($pChar->image_path) }}" class="w-6 h-6 rounded-full object-cover shrink-0 border border-secondary-50" alt="">
                                            @else
                                                <div class="w-6 h-6 rounded-full bg-brand-150 overflow-hidden flex items-center justify-center shrink-0 border border-brand-200">
                                                    <x-icons.default-avatar class="w-4 h-4 text-secondary-150" />
                                                </div>
                                            @endif
                                            <span class="truncate flex-1">{{ $pChar->full_name ?? $pChar->nick_name ?? 'Unnamed' }}</span>
                                            @if($isCharAdded)
                                                <span class="text-app-caption text-[9.5px] text-subtext-90 font-semibold bg-subtext-80 px-1.5 py-0.5 rounded">Added</span>
                                            @endif
                                        </button>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        {{-- Attached Characters List --}}
                        @if($chapterCard->characters && $chapterCard->characters->isNotEmpty())
                            <div class="flex flex-col gap-1.5 mt-2">
                                @foreach($chapterCard->characters as $char)
                                    <div class="flex items-center justify-between p-2 rounded-lg bg-secondary-10/40 border border-secondary-50/60 group">
                                        <div class="flex items-center gap-2 min-w-0">
                                            @if($char->image_path)
                                                <img src="{{ Storage::url($char->image_path) }}" class="w-7 h-7 rounded-full object-cover shrink-0 border border-secondary-50" alt="">
                                            @else
                                                <div class="w-7 h-7 rounded-full bg-secondary-100/40 overflow-hidden flex items-center justify-center shrink-0 border border-secondary-50">
                                                    <x-icons.default-avatar class="w-5 h-5 text-secondary-150" />
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="text-app-body-small text-[12px] font-semibold text-text-80 truncate">{{ $char->full_name ?? $char->nick_name ?? 'Unnamed' }}</p>
                                                @if($char->nick_name && $char->nick_name !== $char->full_name)
                                                    <p class="text-app-body-small text-[10px] text-subtext-90 truncate">"{{ $char->nick_name }}"</p>
                                                @endif
                                            </div>
                                        </div>
                                        <button type="button" wire:click="detachCharacter('{{ $char->character_id }}')"
                                                class="text-secondary-150 hover:text-secondary-200 opacity-50 group-hover:opacity-100 transition-opacity p-1 cursor-pointer" title="Remove character from chapter">
                                            &times;
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
