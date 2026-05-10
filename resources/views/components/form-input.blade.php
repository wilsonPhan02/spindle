@props([
    'label',
    'type' => 'text',
    'placeholder' => '',
    'model' => null,
    'name' => null,
    'options' => []
])

<div class="flex flex-col gap-2 w-full text-left">
    <label class="text-web-body-small font-medium text-text-80">
        {{ $label }}
    </label>

    <div 
        class="relative w-full"
        @if($type === 'password') x-data="{ show: false }" @endif
    >
        @php
            $errorClasses = $errors->has($model) 
                ? 'border-danger-100 ring-1 ring-danger-100' 
                : 'border-subtext-70';
        @endphp

        @if($type === 'select')
            <select
                @if($model) wire:model="{{ $model }}" @endif
                name="{{ $name }}"
                {{ $attributes->merge(['class' => "w-full px-6 pr-10 py-3 bg-bg-main border $errorClasses rounded-sm focus:ring-1/2 focus:border-text-60 outline-none transition-all text-subtext-90 text-web-body-small appearance-none cursor-pointer"]) }}
            >
                <option value="" selected disabled>{{ $placeholder ?: 'Select option' }}</option>
                @foreach($options as $value => $display)
                    <option value="{{ $value }}">{{ $display }}</option>
                @endforeach
            </select>

            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-subtext-90">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </div>

        @else
            @php
                $paddingRight = ($type === 'password' || isset($icon)) ? 'pr-12' : 'pr-6';
            @endphp

            <input
                @if($type === 'password')
                    :type="show ? 'text' : 'password'"
                @else
                    type="{{ $type }}"
                @endif

                placeholder="{{ $placeholder }}"
                @if($model) wire:model="{{ $model }}" @endif
                name="{{ $name }}"

                {{ $attributes->merge(['class' => "w-full px-6 $paddingRight py-3 bg-bg-main border $errorClasses rounded focus:border-text-60 outline-none transition-all text-subtext-90 text-web-body-small placeholder:text-web-body-small"]) }}
            >

            {{-- Slot Ikon --}}
            @if(isset($icon) && $type !== 'password')
                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-subtext-90">
                    {{ $icon }}
                </div>
            @endif

            {{-- Tombol Mata --}}
            @if($type === 'password')
                <button type="button" @click="show = !show" class="absolute right-4 top-1/2 -translate-y-1/2 text-subtext-90 hover:text-text-80 transition-colors z-10">
                    <svg x-show="!show" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.978 9.978 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                    <svg x-show="show" style="display: none;" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                </button>
            @endif
        @endif
    </div>

    @if($model)
        @error($model)
            <p class="text-xs text-red-500 mt-1 italic">{{ $message }}</p>
        @enderror
    @endif
</div>