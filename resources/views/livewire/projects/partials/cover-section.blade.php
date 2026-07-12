            <div
                x-data="{ 
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
                    
                    applyCrop() {
                        if (!this.cropperInstance) return;
                        
                        const canvas = this.cropperInstance.getCroppedCanvas({
                            width: 600,
                            height: 960
                        });
                        
                        canvas.toBlob((blob) => {
                            const file = new File([blob], 'cover.jpg', { type: 'image/jpeg', lastModified: Date.now() });
                            
                            this.cancelCrop();
                            
                            this.isUploading = true;
                            this.progress = 0;
                            
                            @this.upload('cover_image', file,
                                (uploadedFilename) => { this.isUploading = false; },
                                () => { this.isUploading = false; },
                                (e) => { this.progress = e.detail.progress; }
                            );
                        }, 'image/jpeg', 0.9);
                    }
                }"
                @mouseover="hoverCover = true"
                @mouseleave="hoverCover = false"
                class="relative w-full lg:w-[320px] xl:w-[360px] shrink-0 aspect-[1/1.6] z-10"
            >
            
                <x-book-cover :imagePath="$project->cover_image_path" class="w-full h-full" />

                {{-- Inline Cropper UI for Cover --}}
                <div x-show="showCropper" style="display: none;" class="absolute inset-0 z-40 bg-brand-50 rounded-l-md rounded-r-xl border border-brand-200 overflow-hidden flex flex-col">
                    <div class="flex-1 w-full relative">
                        <img x-ref="cropperImg" :src="cropImageUrl" class="block max-w-full" alt="Crop Preview">
                    </div>
                    <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-2 z-50">
                        <button @click="cancelCrop()" type="button" class="px-4 py-1.5 bg-bg-main/90 backdrop-blur text-text-70 text-[11px] font-bold uppercase tracking-wider rounded-md border border-text-60 hover:bg-bg-main shadow-lg transition-colors">{{ __('Cancel') }}</button>
                        <button @click="applyCrop()" type="button" class="px-4 py-1.5 bg-secondary-100/95 backdrop-blur text-bg-main text-[11px] font-bold uppercase tracking-wider rounded-md shadow-lg border border-secondary-200 hover:bg-secondary-200 transition-colors">{{ __('Save') }}</button>
                    </div>
                </div>

                <div x-show="hoverCover && !showCropper" x-transition class="absolute bottom-5 left-5 z-30 flex gap-2">
                    <label class="flex items-center gap-2 px-3.5 py-2 bg-black/75 backdrop-blur-md border border-white/15 rounded-lg cursor-pointer hover:bg-black/90 transition-all shadow-xl text-white">
                        <x-icons.upload class="w-4 h-4 text-white" />
                        <span class="text-white font-semibold text-app-desc-feature">{{ $project->cover_image_path ? __('Change Cover') : __('Upload Cover') }}</span>
                        <input type="file" x-ref="coverInput" class="hidden" accept="image/*"
                            @change="
                                const file = $event.target.files[0];
                                if (file) {
                                    if (file.size > 5 * 1024 * 1024) {
                                        clientError = 'The selected image is too large. The maximum allowed file size is 5MB.';
                                        $event.target.value = '';
                                    } else {
                                        clientError = null;
                                        
                                        const reader = new FileReader();
                                        reader.onload = (e) => { 
                                            cropImageUrl = e.target.result;
                                            showCropper = true;
                                            
                                            $nextTick(() => {
                                                if (cropperInstance) cropperInstance.destroy();
                                                cropperInstance = new Cropper($refs.cropperImg, {
                                                    aspectRatio: 1 / 1.6,
                                                    viewMode: 1,
                                                    dragMode: 'move',
                                                    background: false,
                                                    guides: false,
                                                    center: true,
                                                    highlight: false,
                                                    cropBoxMovable: false,
                                                    cropBoxResizable: false,
                                                    minCropBoxWidth: 100,
                                                    minCropBoxHeight: 160,
                                                });
                                            });
                                        };
                                        reader.readAsDataURL(file);
                                    }
                                }
                            ">
                    </label>

                    @if($project->cover_image_path)
                        <button wire:click="deleteCover" class="flex items-center gap-2 px-3.5 py-2 bg-black/75 backdrop-blur-md border border-danger-100/40 rounded-lg cursor-pointer hover:bg-danger-100/20 transition-all shadow-xl text-danger-100">
                            <x-icons.delete class="w-4 h-4 text-danger-100" />
                            <span class="text-app-desc-feature font-semibold text-danger-100">{{ __('Remove') }}</span>
                        </button>
                    @endif
                </div>
                {{-- Client-side Error --}}
                <template x-if="clientError">
                    <div class="absolute inset-x-4 top-4 bg-danger-100/95 text-bg-main text-[12px] font-medium px-3 py-2.5 rounded shadow-xl z-50 flex items-start gap-2">
                        <svg class="w-4 h-4 mt-0.5 shrink-0 text-bg-main" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span x-text="clientError" class="flex-1 leading-relaxed"></span>
                        <button @click="clientError = null" class="shrink-0 ml-2 p-0.5 hover:bg-black/20 rounded transition-colors" title="{{ __('Dismiss') }}"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                    </div> 
                </template>

                {{-- Server-side Error --}}
                @error('cover_image') 
                    <div x-data="{ show: true }" x-show="show" class="absolute inset-x-4 top-4 bg-danger-100/95 text-bg-main text-[12px] font-medium px-3 py-2.5 rounded shadow-xl z-50 flex items-start gap-2">
                        <svg class="w-4 h-4 mt-0.5 shrink-0 text-bg-main" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span class="flex-1 leading-relaxed">{{ $message }}</span>
                        <button @click="show = false" class="shrink-0 ml-2 p-0.5 hover:bg-black/20 rounded transition-colors" title="{{ __('Dismiss') }}"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                    </div> 
                @enderror

                {{-- Progress Overlay --}}
                <div x-show="isUploading" x-transition class="absolute inset-y-0 left-0 right-4 w-[calc(100%-16px)] bg-secondary-5/80 backdrop-blur-md z-40 flex flex-col items-center justify-center rounded-l-md rounded-r-lg">
                    <svg class="animate-spin h-8 w-8 text-secondary-200 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <div class="text-secondary-200 font-semibold text-sm">{{ __('Uploading...') }} <span x-text="progress + '%'"></span></div>
                    
                    <div class="w-3/4 bg-brand-150 rounded-full h-1.5 mt-3 overflow-hidden shadow-inner mx-auto">
                        <div class="bg-secondary-100 h-full rounded-full transition-all duration-200 ease-out" :style="`width: ${progress}%`"></div>
                    </div>
                </div>
            </div>
