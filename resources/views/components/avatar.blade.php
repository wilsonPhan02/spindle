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
    {{-- LISTENER BARU DI SINI --}}
    @profile-updated.window="photoPreview = $event.detail.avatarUrl; isRemoved = false"
    @open-edit-profile.window="if(@json($editable)) { photoPreview = null; isRemoved = false; if($refs.photo) $refs.photo.value = null }"
    class="flex flex-col items-center shrink-0"
>
    <div 
        {{-- Hapus flex dan items-center di sini, cukup biarkan relative --}}
        class="{{ $size }} rounded-full bg-brand-100 border-2 border-brand-200 overflow-hidden relative group transition-all shrink-0"
        :class="{ 'cursor-pointer hover:border-secondary-200': @json($editable) }"
        @if($editable) @click="$refs.photo.click()" @endif
    >
        {{-- Tampilan FOTO --}}
        <template x-if="photoPreview || ('{{ $imageUrl }}' && !isRemoved)">
            {{-- Gunakan absolute inset-0 agar gambar menempel sempurna ke pinggir lingkaran --}}
            <img :src="photoPreview || '{{ $imageUrl }}'" class="absolute inset-0 w-full h-full object-cover object-center z-10">
        </template>

        {{-- Tampilan PLACEHOLDER --}}
        <template x-if="!photoPreview && (!'{{ $imageUrl }}' || isRemoved)">
            {{-- Gunakan absolute inset-0 juga di sini --}}
            <div class="absolute inset-0 flex items-center justify-center bg-brand-100 z-0">
                <x-icons.default-avatar
                    primaryColor="#D9C5A4"
                    secondaryColor="#81644D" 
                    class="w-full h-full"
                />
            </div>
        </template>

        {{-- Overlay Edit Photo --}}
        @if($editable)
            <div class="absolute inset-0 bg-brand-200/60 backdrop-blur-[1.5px] flex flex-col items-center justify-center gap-2 text-text-70 opacity-0 group-hover:opacity-100 transition-all duration-300 z-20">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-12 h-12">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                </svg>
                <span class="text-web-body-small font-medium uppercase tracking-wider">Edit Photo</span>

                {{-- Tombol Detach --}}
                <template x-if="photoPreview || ('{{ $imageUrl }}' && !isRemoved)">
                    <button 
                        type="button"
                        @click.stop="detach()"
                        class="mt-1 px-3 py-1 bg-text-70/70 hover:bg-text-70/90 text-subtext-60 text-[9px] font-bold rounded-full border border-text-70/90 transition-colors uppercase tracking-tighter"
                    >
                        Detach Photo
                    </button>
                </template>
            </div>

            <input 
                type="file" class="hidden" x-ref="photo" accept="image/*"
                wire:model="{{ $model }}"
                @change="
                    const file = $refs.photo.files[0];
                    if (file) {
                        isRemoved = false;
                        const reader = new FileReader();
                        reader.onload = (e) => { photoPreview = e.target.result; };
                        reader.readAsDataURL(file);
                    }
                "
            >
        @endif
    </div>
</div>