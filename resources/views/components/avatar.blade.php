@props([
    'imageUrl' => null,
    'size' => 'w-48 h-48',
    'editable' => false,
    'model' => 'photo',
])

<div 
    x-data="{ 
        photoPreview: null, 
        isRemoved: false,
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
            this.$refs.photo.value = null;
        },
        
        applyCrop() {
            if (!this.cropperInstance) return;
            
            const canvas = this.cropperInstance.getCroppedCanvas({
                width: 400,
                height: 400
            });
            
            canvas.toBlob((blob) => {
                const file = new File([blob], 'avatar.jpg', { type: 'image/jpeg', lastModified: Date.now() });
                
                this.photoPreview = canvas.toDataURL('image/jpeg');
                this.cancelCrop();
                
                this.isUploading = true;
                this.progress = 0;
                
                @this.upload('{{ $model }}', file,
                    (uploadedFilename) => { this.isUploading = false; },
                    () => { this.isUploading = false; },
                    (e) => { this.progress = e.detail.progress; }
                );
            }, 'image/jpeg', 0.9);
        },
        
        @if($editable)
            detach() {
                this.photoPreview = null;
                this.isRemoved = true;
                this.$refs.photo.value = null;
                @this.set('{{ $model }}', null);
                @this.set('is_photo_removed', true);
            }
        @endif
    }" 

    @profile-updated.window="photoPreview = $event.detail.avatarUrl; isRemoved = false"
    @open-edit-profile.window="if(@json($editable)) { photoPreview = null; isRemoved = false; if($refs.photo) $refs.photo.value = null }"
    class="flex flex-col items-center shrink-0 relative"
>
    <div 
        class="{{ $size }} rounded-full bg-brand-100 border-2 border-brand-200 overflow-hidden relative group transition-all shrink-0"
        :class="{ 'cursor-pointer hover:border-secondary-200': @json($editable) }"
        @if($editable) @click="$refs.photo.click()" @endif
    >
        {{-- PHOTO Display --}}
        <template x-if="photoPreview || ('{{ $imageUrl }}' && !isRemoved)">
            <img :src="photoPreview || '{{ $imageUrl }}'" class="absolute inset-0 w-full h-full object-cover object-center z-10">
        </template>

        {{-- PLACEHOLDER Display --}}
        <template x-if="!photoPreview && (!'{{ $imageUrl }}' || isRemoved)">
            <div class="absolute inset-0 flex items-center justify-center bg-brand-100 z-0">
                <x-icons.default-avatar
                    primaryColor="#D9C5A4"
                    secondaryColor="#81644D" 
                    class="w-full h-full"
                />
            </div>
        </template>

        {{-- Progress Overlay --}}
        <div x-show="isUploading" x-transition class="absolute inset-0 bg-brand-200/80 backdrop-blur-sm z-30 flex flex-col items-center justify-center text-secondary-200 rounded-full transition-all">
            <svg class="animate-spin h-8 w-8 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <span class="text-sm font-bold font-mono" x-text="progress + '%'"></span>
        </div>

        {{-- Overlay Edit Photo --}}
        @if($editable)
            <div class="absolute inset-0 bg-brand-200/60 backdrop-blur-[1.5px] flex flex-col items-center justify-center gap-1.5 sm:gap-2 text-text-70 opacity-0 group-hover:opacity-100 transition-all duration-300 z-20">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="{{ str_contains($size, 'w-32') || str_contains($size, 'w-24') ? 'w-8 h-8' : 'w-12 h-12' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                </svg>
                <span class="{{ str_contains($size, 'w-32') || str_contains($size, 'w-24') ? 'text-[10px]' : 'text-web-body-small' }} font-medium uppercase tracking-wider text-center leading-tight">{{ __('Edit Photo') }}</span>

                {{-- Detach Button --}}
                <template x-if="photoPreview || ('{{ $imageUrl }}' && !isRemoved)">
                    <button 
                        type="button"
                        @click.stop="detach()"
                        class="mt-1 px-3 py-1 bg-text-70/70 hover:bg-text-70/90 text-subtext-60 text-[9px] font-bold rounded-full border border-text-70/90 transition-colors uppercase tracking-tighter"
                    >
                        {{ __('Detach Photo') }}
                    </button>
                </template>
            </div>

            <input 
                type="file" class="hidden" x-ref="photo" accept="image/*"
                @change="
                    const file = $refs.photo.files[0];
                    if (file) {
                        if (file.size > 5 * 1024 * 1024) {
                            clientError = '{{ __('The selected image is too large. The maximum allowed file size is 5MB.') }}';
                            $refs.photo.value = '';
                        } else {
                            clientError = null;
                            isRemoved = false;
                            
                            const reader = new FileReader();
                            reader.onload = (e) => { 
                                cropImageUrl = e.target.result;
                                showCropper = true;
                                
                                $nextTick(() => {
                                    if (cropperInstance) cropperInstance.destroy();
                                    cropperInstance = new Cropper($refs.cropperImg, {
                                        aspectRatio: 1,
                                        viewMode: 1,
                                        dragMode: 'move',
                                        background: false,
                                        guides: false,
                                        center: true,
                                        highlight: false,
                                        cropBoxMovable: false,
                                        cropBoxResizable: false,
                                        minCropBoxWidth: 200,
                                        minCropBoxHeight: 200,
                                    });
                                });
                            };
                            reader.readAsDataURL(file);
                        }
                    }
                "
            >
        @endif
    </div>

    {{-- Client-side Error Message --}}
    <template x-if="clientError">
        <div class="absolute bottom-3 translate-y-1/2 left-1/2 -translate-x-1/2 bg-danger-100/95 text-bg-main text-[11px] font-medium px-3 py-2 rounded-md shadow-xl w-max max-w-[160%] text-center flex items-start gap-1.5 z-40 whitespace-normal">
            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <span x-text="clientError" class="flex-1 leading-snug text-left"></span>
            <button @click.stop="clientError = null" class="shrink-0 ml-1 p-0.5 hover:bg-black/20 rounded transition-colors" title="{{ __('Dismiss') }}"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
        </div>
    </template>

    {{-- Server-side Error Message --}}
    @error($model)
        <div x-data="{ show: true }" x-show="show" class="absolute bottom-3 translate-y-1/2 left-1/2 -translate-x-1/2 bg-danger-100/95 text-bg-main text-[11px] font-medium px-3 py-2 rounded-md shadow-xl w-max max-w-[160%] text-center flex items-start gap-1.5 z-40 whitespace-normal">
            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <span class="flex-1 leading-snug text-left">{{ $message }}</span>
            <button @click.stop="show = false" class="shrink-0 ml-1 p-0.5 hover:bg-black/20 rounded transition-colors" title="{{ __('Dismiss') }}"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
        </div>
    @enderror

    {{-- Cropper Modal --}}
    <div x-show="showCropper" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm">
        <div class="bg-bg-main w-full max-w-lg rounded-2xl overflow-hidden shadow-2xl flex flex-col" @click.away="cancelCrop()">
            <div class="p-4 border-b border-black/10 flex justify-between items-center bg-brand-10">
                <h3 class="text-app-heading-3 text-text-100">{{ __('Adjust Profile Photo') }}</h3>
                <button @click="cancelCrop()" class="p-2 text-text-60 hover:bg-black/5 rounded-full transition-colors" title="{{ __('Cancel') }}"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            
            {{-- Cropper Area --}}
            <div class="p-6 bg-brand-50 w-full h-[400px] flex items-center justify-center">
                <div class="w-full h-full overflow-hidden rounded-md border border-brand-150">
                    <img x-ref="cropperImg" :src="cropImageUrl" class="max-w-full block" alt="{{ __('Picture') }}">
                </div>
            </div>
            
            <div class="p-4 border-t border-black/10 bg-brand-10 flex justify-end gap-3">
                <button @click="cancelCrop()" type="button" class="px-5 py-2 text-app-desc-feature font-semibold text-text-70 hover:bg-black/5 rounded-lg transition-colors">{{ __('Cancel') }}</button>
                <button @click="applyCrop()" type="button" class="px-5 py-2 bg-secondary-100 text-bg-main text-app-desc-feature font-semibold rounded-lg shadow hover:bg-secondary-200 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    {{ __('Crop & Save') }}
                </button>
            </div>
        </div>
    </div>
</div>