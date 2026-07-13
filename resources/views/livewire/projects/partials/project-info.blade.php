            <div class="flex-1 min-w-0 relative">
                <div class="static lg:absolute inset-0 bg-brand-50 border border-brand-150 p-8 pb-16 rounded-lg flex flex-col">

                <div class="flex justify-between items-start mb-4" x-data="{
                    showIconPicker: false, 
                    tab: 'emoji',
                    async initPicmo() {
                        if (!window.EmojiMart) {
                            await new Promise(resolve => {
                                const script = document.createElement('script');
                                script.src = 'https://cdn.jsdelivr.net/npm/emoji-mart@latest/dist/browser.js';
                                script.onload = resolve;
                                document.head.appendChild(script);
                            });
                        }
                        if (!window.emojiMartData) {
                            const response = await fetch('https://cdn.jsdelivr.net/npm/@emoji-mart/data');
                            window.emojiMartData = await response.json();
                        }
                        this.renderPicker();
                    },
                    renderPicker() {
                        const pickerOptions = {
                            data: window.emojiMartData,
                            onEmojiSelect: (e) => {
                                $wire.setEmoji(e.native);
                                this.showIconPicker = false;
                            },
                            theme: 'light',
                            set: 'native',
                            previewPosition: 'none',
                            skinTonePosition: 'search',
                            navPosition: 'bottom',
                            maxFrequentRows: 1
                        };
                        const picker = new window.EmojiMart.Picker(pickerOptions);
                        
                        // Prevent context menu directly on the picker element 
                        // (Alpine's @contextmenu on wrapper fails over Shadow DOM boundaries in some browsers)
                        picker.addEventListener('contextmenu', (e) => e.preventDefault());
                        
                        // EmojiMart v5 uses --rgb-accent for the active tab indicator and focus rings.
                        // We set it to Spindle's brown (140, 117, 88 in RGB)
                        picker.style.setProperty('--rgb-accent', '140, 117, 88');
                        
                        this.$refs.picmoContainer.innerHTML = '';
                        this.$refs.picmoContainer.appendChild(picker);

                        // Inject smooth scrolling CSS into the Shadow DOM
                        setTimeout(() => {
                            try {
                                const style = document.createElement('style');
                                style.textContent = `
                                    .scroll {
                                        scroll-behavior: smooth !important;
                                    }
                                `;
                                picker.shadowRoot.appendChild(style);

                                // Optimization: Block hover events inside the skin tone menu
                                // EmojiMart v5 causes massive lag by re-rendering all emojis on hover to preview skin tones.
                                // Stopping these events prevents the live preview, eliminating the lag entirely.
                                ['mouseover', 'mouseout', 'mouseenter', 'mouseleave'].forEach(evt => {
                                    picker.shadowRoot.addEventListener(evt, (e) => {
                                        if (e.target.closest && e.target.closest('.menu')) {
                                            e.stopPropagation();
                                        }
                                    }, true); // Use capture phase to intercept before Preact
                                });
                            } catch(e) {}
                        }, 50);
                    }
                }" x-init="$watch('showIconPicker', value => { if(value && tab === 'emoji') initPicmo() }); $watch('tab', value => { if(value === 'emoji' && showIconPicker) initPicmo() })">
                    <div class="flex-1 min-w-0 mr-4">
                        
                        {{-- Ikon Dinamis --}}
                        <div class="relative inline-block group mb-3">
                            <div class="w-10 h-10 flex items-center justify-center cursor-pointer rounded transition-colors group-hover:bg-brand-100"
                                title="{{ $project->icon_type === 'default' ? __('Add Icon') : __('Change Icon') }}"
                                @click="showIconPicker = !showIconPicker">

                                @if($project->icon_type === 'emoji')
                                    <span class="text-[32px] leading-none">{{ $project->icon }}</span>
                                @elseif($project->icon_type === 'image' && $project->icon)
                                    <img src="{{ asset('storage/' . $project->icon) }}"
                                        alt="{{ __('Project Icon') }}"
                                        class="w-8 h-8 object-cover rounded">
                                @else
                                    <x-icons.sidebar-book class="w-8 h-8 text-secondary-100" />
                                @endif
                            </div>
                        </div>

                        {{-- Pop-up Notion Style --}}
                        <div x-show="showIconPicker" 
                            x-on:close-icon-picker.window="showIconPicker = false"
                            @click.away="showIconPicker = false"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                            x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
                            class="absolute left-10 top-12 z-50 w-[352px] rounded-lg border border-brand-200 bg-bg-main shadow-xl flex flex-col origin-top-left"
                            style="display: none;">
                            
                            {{-- Header & Tabs --}}
                            <div class="flex items-center justify-between px-4 border-b border-brand-200">
                                <div class="flex gap-4">
                                    <button @click="tab = 'emoji'" 
                                            :class="tab === 'emoji' ? 'border-text-80 text-text-80' : 'border-transparent text-text-60 hover:text-text-80'"
                                            class="px-1 py-2.5 text-app-feature border-b-2 transition-colors -mb-[1px]">
                                        {{ __('Emoji') }}
                                    </button>
                                    <button @click="tab = 'upload'" 
                                            :class="tab === 'upload' ? 'border-text-80 text-text-80' : 'border-transparent text-text-60 hover:text-text-80'"
                                            class="px-1 py-2.5 text-app-feature border-b-2 transition-colors -mb-[1px]">
                                        {{ __('Upload') }}
                                    </button>
                                </div>
                                
                                {{-- Tombol Remove --}}
                                <button wire:click="removeIcon" 
                                        @click="showIconPicker = false"
                                        class="text-danger-100 hover:bg-danger-100/10 transition-colors flex items-center gap-1 p-1.5 rounded-md" 
                                        title="{{ __('Remove Icon') }}">
                                    <x-icons.delete class="w-4 h-4" />
                                </button>
                            </div>

                            {{-- Tab Emoji (Web Component / EmojiMart) --}}
                            <div x-show="tab === 'emoji'" wire:ignore @contextmenu.prevent>
                                <div x-ref="picmoContainer" class="rounded-b-lg overflow-hidden flex items-center justify-center bg-bg-main min-h-[350px]">
                                    <div class="py-12 flex justify-center w-full">
                                        <svg class="animate-spin h-8 w-8 text-secondary-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    </div>
                                </div>
                            </div>

                            {{-- Tab Upload Image --}}
                            <div x-show="tab === 'upload'" class="p-4 flex flex-col min-h-[318px]"
                                x-data="{ 
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
                                        if(this.$refs.iconInput) this.$refs.iconInput.value = null;
                                    },
                                    
                                    applyCrop() {
                                        if (!this.cropperInstance) return;
                                        
                                        const canvas = this.cropperInstance.getCroppedCanvas({
                                            width: 400,
                                            height: 400
                                        });
                                        
                                        canvas.toBlob((blob) => {
                                            const file = new File([blob], 'icon.jpg', { type: 'image/jpeg', lastModified: Date.now() });
                                            
                                            this.cancelCrop();
                                            
                                            this.isUploading = true;
                                            this.progress = 0;
                                            
                                            @this.upload('icon_image', file,
                                                (uploadedFilename) => { this.isUploading = false; },
                                                () => { this.isUploading = false; },
                                                (e) => { this.progress = e.detail.progress; }
                                            );
                                        }, 'image/jpeg', 0.9);
                                    }
                                }">
                                
                                <div x-show="!showCropper">
                                
                                <p class="text-text-60 text-app-desc-feature mb-3">{{ __('Upload custom image (Max 2MB)') }}</p>
                                
                                <input type="file" id="icon-image-upload" x-ref="iconInput"
                                    class="hidden" accept=".jpg,.jpeg,.png,.svg,.webp"
                                    @change="
                                        const file = $event.target.files[0];
                                        if (file) {
                                            if (file.size > 2 * 1024 * 1024) {
                                                clientError = '{{ __('The selected image is too large. The maximum allowed file size is 2MB.') }}';
                                                $event.target.value = '';
                                            } else {
                                                clientError = null;
                                                
                                                const reader = new FileReader();
                                                reader.onload = (e) => { 
                                                    cropImageUrl = e.target.result;
                                                    showCropper = true;
                                                    
                                                    $nextTick(() => {
                                                        if (cropperInstance) cropperInstance.destroy();
                                                        cropperInstance = new Cropper($refs.iconCropperImg, {
                                                            aspectRatio: 1,
                                                            viewMode: 1,
                                                            dragMode: 'move',
                                                            background: false,
                                                            guides: false,
                                                            center: true,
                                                            highlight: false,
                                                            cropBoxMovable: false,
                                                            cropBoxResizable: false,
                                                            minCropBoxWidth: 100,
                                                            minCropBoxHeight: 100,
                                                        });
                                                    });
                                                };
                                                reader.readAsDataURL(file);
                                            }
                                        }
                                    ">
                                
                                <div class="flex items-center mb-3">
                                    <label for="icon-image-upload" 
                                        class="mr-3 py-1.5 px-3 rounded-md text-app-desc-feature font-semibold bg-brand-150 text-text-80 hover:bg-brand-200 transition-colors cursor-pointer"
                                        :class="{ 'opacity-50 pointer-events-none': isUploading }">
                                        {{ __('Browse Image') }}
                                    </label>
                                    
                                    <div class="text-app-desc-feature text-text-70 flex-1 min-w-0 flex items-center justify-between">
                                        <span x-show="!isUploading" wire:loading.remove wire:target="icon_image">
                                            {{ $icon_image ? __('Image selected') : __('No file chosen') }}
                                        </span>
                                        <span x-show="isUploading" class="text-secondary-100 font-medium">
                                            {{ __('Uploading...') }} <span x-text="progress + '%'"></span>
                                        </span>
                                    </div>
                                </div>

                                {{-- Progress Bar --}}
                                <div x-show="isUploading" x-transition class="w-full bg-brand-150 rounded-full h-1.5 mb-3 overflow-hidden shadow-inner">
                                    <div class="bg-secondary-100 h-full rounded-full transition-all duration-200 ease-out" :style="`width: ${progress}%`"></div>
                                </div>

                                {{-- Client-side Error --}}
                                <template x-if="clientError">
                                    <div class="bg-danger-100/10 border border-danger-100/20 text-danger-100 text-[13px] font-medium px-3 py-2 rounded-md mt-2 mb-3 flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        <span x-text="clientError"></span>
                                    </div> 
                                </template>

                                {{-- Server-side Error --}}
                                @error('icon_image') 
                                    <div class="bg-danger-100/10 border border-danger-100/20 text-danger-100 text-[13px] font-medium px-3 py-2 rounded-md mt-2 mb-3 flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        <span>{{ $message }}</span>
                                    </div> 
                                @enderror
                                
                                @if ($icon_image)
                                    <div class="mb-3 flex flex-col items-center p-3 border border-dashed border-brand-300 rounded-md bg-card-hover">
                                        <img src="{{ $icon_image->temporaryUrl() }}" class="w-16 h-16 object-cover rounded shadow-sm">
                                    </div>
                                    
                                    <button wire:click="saveIcon" 
                                            class="w-full py-2 bg-secondary-100 text-bg-main font-medium text-app-feature rounded-md hover:bg-secondary-200 transition-colors">
                                        {{ __('Submit Image') }}
                                    </button>
                                @endif
                                </div>
                                
                                {{-- Inline Cropper UI for Icon --}}
                                <div x-show="showCropper" style="display: none;" class="flex-1 flex flex-col gap-3">
                                    <div class="w-full aspect-square bg-brand-50 rounded border border-brand-200 flex items-center justify-center overflow-hidden">
                                        <img x-ref="iconCropperImg" :src="cropImageUrl" class="block max-w-full" alt="{{ __('Crop Preview') }}">
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <button @click="cancelCrop()" type="button" class="px-3 py-1.5 text-app-desc-feature font-semibold text-text-70 hover:bg-brand-100 rounded transition-colors">{{ __('Cancel') }}</button>
                                        <button @click="applyCrop()" type="button" class="px-3 py-1.5 bg-secondary-100 text-bg-main text-app-desc-feature font-semibold rounded shadow hover:bg-secondary-200 transition-colors">{{ __('Save') }}</button>
                                    </div>
                                </div>
                            </div>

                        </div>

                        @php
                            $titleLen = strlen($title);
                            $dtlTitleSize = 'text-[32px]';
                            if ($titleLen >= 25) { $dtlTitleSize = 'text-[22px]'; } elseif ($titleLen >= 15) { $dtlTitleSize = 'text-[26px]'; }
                        @endphp

                        <div x-data="{ editingTitle: false, hoverTitle: false, localTitle: @entangle('title') }" @mouseover="hoverTitle = true" @mouseleave="hoverTitle = false" x-on:livewire:navigated.window="localTitle = $wire.title" class="flex items-center gap-3 group relative">
                            <h1
                                x-show="!editingTitle"
                                @dblclick="editingTitle = true; setTimeout(() => $refs.titleInput.focus(), 50)"
                                class="{{ $dtlTitleSize }} text-app-title-1 text-text-80 transition-colors leading-tight cursor-pointer select-none group-hover:text-secondary-200 truncate"
                            >
                                <span x-text="localTitle || '{{ __('Untitled Project') }}'"></span>
                            </h1>
                            <button x-show="hoverTitle && !editingTitle" @click="editingTitle = true; setTimeout(() => $refs.titleInput.focus(), 50)" class="stroke-2 text-secondary-200 transition-colors shrink-0">
                                <x-icons.rename class="w-5 h-5" />
                            </button>

                            <div class="relative w-full" x-show="editingTitle">
                                <input
                                    x-model="localTitle"
                                    x-ref="titleInput"
                                    maxlength="100"
                                    @click.outside="if(editingTitle) { $wire.saveTitle(); editingTitle = false; }"
                                    @keydown.enter="$wire.saveTitle(); editingTitle = false"
                                    @keydown.escape="editingTitle = false; localTitle = '{{ addslashes($project->title) }}'"
                                    class="{{ $dtlTitleSize }} text-app-title-1 text-text-80 bg-transparent border-b-2 border-secondary-100 outline-none w-full focus:border-secondary-200 focus:ring-0 px-0 py-1 pr-14"
                                />
                                <span class="absolute right-0 bottom-2 text-xs text-subtext-80 font-medium" x-text="(localTitle ? localTitle.length : 0) + '/100'"></span>
                            </div>
                        </div>
                        <p class="text-app-body-large text-subtext-100 mt-2 truncate">{{ __('from ') }}<span class="text-text-80" title="{{ $project->section->title ?? __('Uncategorized') }}">{{ \Illuminate\Support\Str::limit($project->section->title ?? __('Uncategorized'), 30) }}</span></p>
                    </div>

                    <div class="flex items-center gap-3 shrink-0" @limit-reached.window="alert('{{ __('Marked projects have reached the maximum limit (10). You cannot mark more projects.') }}')">
                        <button wire:click="togglePin" class="text-secondary-100 cursor-pointer hover:text-secondary-200 transition-colors focus:outline-none" title="{{ $project->is_pinned ? __('Unmark Project') : __('Mark Project') }}">
                            @if($project->is_pinned)
                                <x-icons.bookmark-solid class="w-6 h-6" />
                            @else
                                <x-icons.bookmark class="w-6 h-6" />
                            @endif
                        </button>
                    </div>
                </div>

                <div x-data="{
                    addingCat: false,
                    addCount: 0,
                    isDown: false,
                    startX: 0,
                    scrollLeft: 0,
                    startDrag(e) {
                        this.isDown = true;
                        this.startX = e.pageX - this.$refs.scrollContainer.offsetLeft;
                        this.scrollLeft = this.$refs.scrollContainer.scrollLeft;
                    },
                    endDrag() {
                        this.isDown = false;
                    },
                    doDrag(e) {
                        if (!this.isDown) return;
                        e.preventDefault();
                        const x = e.pageX - this.$refs.scrollContainer.offsetLeft;
                        const walk = (x - this.startX) * 1.5;
                        this.$refs.scrollContainer.scrollLeft = this.scrollLeft - walk;
                    }
                }" class="mb-0 w-full max-w-full min-w-0">
                    <div class="flex items-center gap-3 mb-2">
                        <x-icons.category class="w-4 h-4 text-text-80" />
                        <span class="text-app-feature text-text-80">{{ __('Categories') }}</span>
                    </div>

                    <div
                        x-ref="scrollContainer"
                        @mousedown="startDrag"
                        @mouseleave="endDrag"
                        @mouseup="endDrag"
                        @mousemove="doDrag"
                        @wheel="$event.preventDefault(); $el.scrollLeft += ($event.deltaX !== 0 ? $event.deltaX : $event.deltaY)"
                        class="flex gap-2 items-center overflow-x-auto pb-5 cursor-grab active:cursor-grabbing scroll-smooth [&::-webkit-scrollbar]:h-1.5 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-secondary-100 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-button]:hidden [scrollbar-width:thin] [scrollbar-color:#A08866_transparent]"
                    >
                        <button x-show="!addingCat" @click="addingCat = true; addCount = 0; setTimeout(() => $refs.catInput.focus(), 50)" class="shrink-0 flex items-center gap-1 cursor-pointer rounded-md bg-brand-100 hover:bg-brand-200 text-secondary-150 hover:text-secondary-150 transition-colors p-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            @if($project->categories->count() == 0)
                                <span class="text-app-desc-feature">{{ __('Add category') }}</span>
                            @endif
                        </button>

                        <div x-show="addingCat" class="flex items-center gap-1 bg-brand-100 pl-3 pr-1.5 py-2 rounded-md border border-brand-150 relative shrink-0">
                                <input type="text" maxlength="20" 
                                        @input="addCount = $event.target.value.length" x-model="$wire.newCategoryName" x-ref="catInput" 
                                        @keyup.enter="addingCat = false; $wire.addCategory()" 
                                        @blur="if(addingCat) { addingCat = false; $wire.set('newCategoryName', ''); }"
                                        @keydown.escape="addingCat = false; $wire.set('newCategoryName', '');" 
                                        placeholder="{{ __('Category...') }}" 
                                        class="w-28 text-app-body-small bg-transparent border-b border-secondary-100 outline-none px-1 py-0 text-text-70 focus:border-secondary-200"/>
                                
                                <button @mousedown.prevent="if(addCount > 0) { addingCat = false; $wire.addCategory(); } else { addingCat = false; $wire.set('newCategoryName', ''); }" 
                                        class="text-secondary-150 hover:text-secondary-200 transition-colors shrink-0">
                                    <svg x-show="addCount === 0" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    <svg x-show="addCount > 0" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </button>

                            <span class="absolute -bottom-3.5 right-1 text-[9px] text-subtext-90 font-medium" x-text="addCount + '/20'"></span>
                        </div>

                        @foreach($project->categories as $category)
                            <div x-data="{ editingCat: false, hoverCat: false, count: {{ strlen($category->name) }} }" 
                                 @mouseenter="hoverCat = true" 
                                 @mouseleave="hoverCat = false"
                                 class="relative group shrink-0">
                                
                                <div x-show="!editingCat" @dblclick="editingCat = true; $nextTick(() => $refs.editCatInput.focus())" title="{{ $category->name }}" class="cursor-pointer px-3 py-2 rounded-md bg-brand-100 text-app-body-small text-text-80 flex gap-1.5 items-center border border-transparent group-hover:border-brand-150 transition-colors">
                                    <span class="select-none truncate max-w-[130px] block">
                                        {{ $category->name }}
                                    </span>
                                    
                                    <button wire:click.stop="deleteCategory('{{ $category->category_id }}')" x-show="hoverCat" class="text-secondary-100 hover:text-secondary-200 transition-colors shrink-0 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>

                                <div x-show="editingCat" class="flex items-center gap-1 bg-brand-100 pl-3 pr-1.5 py-2 rounded-md border border-secondary-150 relative">
                                        <input x-ref="editCatInput" 
                                            value="{{ $category->name }}" 
                                            maxlength="20" 
                                            @input="count = $event.target.value.length" 
                                            @keyup.enter="$el.blur()" 
                                            @blur="editingCat = false; $wire.renameCategory('{{ $category->category_id }}', $el.value)" 
                                            @keydown.escape="editingCat = false; $refs.editCatInput.value = '{{ addslashes($category->name) }}'; 
                                            count = {{ strlen($category->name) }}" 
                                            class="w-24 text-app-body-small bg-transparent border-b border-secondary-100 outline-none px-1 py-0 text-text-70 focus:border-secondary-200" />
                                        
                                        <button @mousedown.prevent="$refs.editCatInput.blur()" class="text-secondary-100 hover:text-secondary-200 transition-colors shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </button>

                                    <span class="absolute -bottom-3.5 right-1 text-[9px] text-subtext-90 font-medium" x-text="count + '/20'"></span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="h-px bg-brand-100 w-full mb-4"></div>

                <div x-data="{
                    editingSyn: false,
                    hoverSyn: false,
                    isOverflowing: false,
                    isAtBottom: false,
                    localSyn: @entangle('synopsis'),
                    checkScroll() {
                        if(this.$refs.synText) {
                            const el = this.$refs.synText;
                            this.isOverflowing = el.scrollHeight > el.clientHeight;
                            this.isAtBottom = el.scrollHeight - el.scrollTop <= el.clientHeight + 2;
                        }
                    },
                    init() {
                        setInterval(() => this.checkScroll(), 1000);
                        this.$watch('localSyn', () => this.$nextTick(() => this.checkScroll()));
                        setTimeout(() => this.checkScroll(), 200);
                    }
                }"
                @resize.window="checkScroll()"
                class="w-full lg:flex-1 lg:min-h-0 flex flex-col">
                    <div @mouseover="hoverSyn = true" @mouseleave="hoverSyn = false" class="flex items-center gap-3 mb-2 shrink-0">
                        <span class="text-app-heading-2 text-text-80">{{ __('Synopsis') }}</span>
                        <button x-show="hoverSyn && !editingSyn" 
                                @click="editingSyn = true; setTimeout(() => $refs.synInput.focus(), 50)" 
                                class="text-secondary-100 hover:text-secondary-200 transition-colors">
                            <x-icons.rename class="w-4 h-4" />
                        </button>
                    </div>

                    <div x-show="!editingSyn" class="lg:flex-1 lg:min-h-0 flex flex-col relative pb-4">
                        <div class="relative group w-full lg:flex-1 lg:min-h-0 shrink flex flex-col">
                            <div
                                x-ref="synText"
                                @scroll="checkScroll()"
                                @dblclick="editingSyn = true; setTimeout(() => $refs.synInput.focus(), 50)"
                                class="text-app-body-medium text-text-80 leading-[1.6] select-none cursor-pointer w-full shrink min-h-0 max-h-[140px] lg:max-h-none lg:flex-1 overflow-y-auto pr-3 custom-scrollbar"
                            >
                                <div x-show="localSyn.trim() !== ''" class="whitespace-pre-wrap" x-text="localSyn.trim()"></div>
                                <div x-show="localSyn.trim() === ''" class="text-app-desc-feature text-text-60">{{ __('Write your synopsis here!') }}</div>
                            </div>
                            <div x-show="isOverflowing && !isAtBottom" class="absolute bottom-0 left-0 w-full h-12 bg-gradient-to-t from-secondary-5 via-secondary-5/80 to-transparent pointer-events-none transition-opacity duration-300"></div>
                        </div>
                    </div>

                    <textarea
                        x-show="editingSyn"
                        x-model="localSyn"
                        x-ref="synInput"
                        @click.outside="if(editingSyn) { $wire.saveSynopsis(); editingSyn = false; $nextTick(() => checkScroll()); }"
                        @keydown.ctrl.enter="$wire.saveSynopsis(); editingSyn = false; $nextTick(() => checkScroll())"
                        @keydown.escape="editingSyn = false; localSyn = `{{ addslashes($project->synopsis ?? '') }}`; $nextTick(() => checkScroll())"
                        class="w-full mt-2 lg:flex-1 lg:min-h-0 min-h-[150px] text-app-body-medium text-text-60 bg-transparent border-2 border-secondary-10 rounded-md outline-none resize-none p-4 focus:border-secondary-100 transition-colors custom-scrollbar"
                    ></textarea>
                </div>

                <div class="absolute bottom-4 text-app-desc-feature text-[11px] left-8 right-8 flex justify-between items-center">
                    <button
                        @click="$dispatch('open-archive-project-dialog')"
                        class="flex items-center gap-1.5 px-2 py-1 -ml-2 rounded-md text-[11px] font-medium text-secondary-100 hover:text-warning-100 hover:bg-warning-100/10 transition-colors opacity-70 hover:opacity-100">
                        <x-icons.archive class="w-3.5 h-3.5" /> {{ __('Move To Archive') }}
                    </button>
                    <div
                        x-data="{
                            diffSeconds: 0,
                            clientStartTime: 0,
                            diffText: '{{ $project->updated_at->diffForHumans() }}',
                            lastRenderTime: null,
                            init() {
                                this.updateTime();
                                setInterval(() => this.updateTime(), 1000);
                            },
                            updateTime() {
                                if (this.$refs.renderTime) {
                                    const newRenderTime = parseFloat(this.$refs.renderTime.innerText);
                                    if (this.lastRenderTime !== newRenderTime) {
                                        this.diffSeconds = Math.abs(parseInt(this.$refs.serverDiff.innerText)) || 0;
                                        this.lastRenderTime = newRenderTime;
                                        this.clientStartTime = Math.floor(Date.now() / 1000);
                                    }
                                }
                                
                                const now = Math.floor(Date.now() / 1000);
                                const elapsedSinceRender = now - this.clientStartTime;
                                const totalDiff = this.diffSeconds + Math.max(0, elapsedSinceRender);
                                
                                if (totalDiff < 60) {
                                    this.diffText = '{{ __('just now') }}';
                                } else if (totalDiff < 120) {
                                    this.diffText = '{{ __('1 minute ago') }}';
                                } else if (totalDiff < 3600) {
                                    this.diffText = Math.floor(totalDiff / 60) + ' ' + '{{ __('minutes ago') }}';
                                } else if (totalDiff < 7200) {
                                    this.diffText = '{{ __('1 hour ago') }}';
                                } else if (totalDiff < 86400) {
                                    this.diffText = Math.floor(totalDiff / 3600) + ' ' + '{{ __('hours ago') }}';
                                } else {
                                    this.diffText = '{{ $project->updated_at->diffForHumans() }}';
                                }
                            }
                        }"
                        class="text-subtext-100"
                    >
                        <span x-ref="serverDiff" class="hidden">{{ abs(now()->timestamp - $project->updated_at->timestamp) }}</span>
                        <span x-ref="renderTime" class="hidden">{{ microtime(true) }}</span>
                        {{ __('Last Edited') }} <span x-text="diffText"></span>
                    </div>
                </div>
            </div>
            </div>
        </div>
