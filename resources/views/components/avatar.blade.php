@props([
    'imageUrl' => null,
    'size' => 'w-48 h-48',
    'editable' => false,
    'model' => 'photo',
])

<div 
    x-data="{ photoPreview: null }" 
    @open-edit-profile.window="photoPreview = null; $refs.photo.value = null"
    class="flex flex-col items-center"
>
    <div 
        class="{{ $size }} rounded-full bg-brand-100 border-2 border-brand-200 flex items-center justify-center overflow-hidden relative group transition-all"
        :class="{ 'cursor-pointer hover:border-secondary-200': @json($editable) }"
        @if($editable) @click="$refs.photo.click()" @endif
    >
        <template x-if="photoPreview || '{{ $imageUrl }}'">
            <img :src="photoPreview || '{{ $imageUrl }}'" class="w-full h-full object-cover">
        </template>

        {{-- Tampilan Placeholder --}}
        <template x-if="!photoPreview && !'{{ $imageUrl }}'">
            <x-icons.default-avatar
                primaryColor="#D9C5A4" {{--secondary-10--}}
                secondaryColor="#81644D" 
                class="w-50 h-50"
            />
        </template>

        {{-- Overlay Edit Photo --}}
        @if($editable)
            <div class="absolute inset-0 bg-brand-200/60 backdrop-blur-[1/2px] flex flex-col items-center justify-center gap-2 text-text-70 opacity-0 group-hover:opacity-100 transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-12 h-12">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                </svg>
                <span class="text-web-body-small font-medium uppercase tracking-wider">Edit Photo</span>
            </div>

            <input 
                type="file" class="hidden" x-ref="photo" accept="image/*"
                wire:model="{{ $model }}"
                @change="
                    const file = $refs.photo.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (e) => { photoPreview = e.target.result; };
                        reader.readAsDataURL(file);
                    }
                "
            >
        @endif
    </div>
</div>