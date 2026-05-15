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

    <div class="relative w-full" @if($type === 'password') x-data="{ show: false }" @endif>
        @php
            $errorClasses = $errors->has($model) 
                ? 'border-danger-100 ring-1 ring-danger-100' 
                : 'border-subtext-70';
        @endphp

        @if($type === 'select')
            {{-- CUSTOM SELECT --}}
            <div x-data="{ open: false, selected: @entangle($model) }" class="relative w-full">
                <button type="button" @click="open = !open" @click.away="open = false"
                    {{ $attributes->merge(['class' => "w-full px-6 pr-4 py-3 bg-bg-main border $errorClasses rounded-sm focus:ring-1/2 focus:border-secondary-250 outline-none transition-all text-subtext-90 text-web-body-small flex items-center justify-between cursor-pointer"]) }}>
                    <span x-text="selected ? ({{ json_encode($options) }}[selected] || '{{ $placeholder }}') : '{{ $placeholder ?: 'Select option' }}'"></span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    class="absolute z-50 w-full mt-1 overflow-hidden border-1 rounded-sm bg-brand-10 border-secondary-50 shadow-2xl" style="display: none;">
                    <div class="max-h-60 overflow-y-auto custom-scrollbar">
                        @foreach($options as $value => $display)
                            <div @click="selected = '{{ $value }}'; open = false"
                                class="px-6 py-2 cursor-pointer text-text-80 text-web-body-small transition-all duration-200 flex items-center justify-between group hover:bg-brand-100 hover:text-text-100"
                                :class="selected === '{{ $value }}' ? 'bg-brand-100/50 text-text-100 font-medium' : ''">
                                <span class="capitalize tracking-wide">{{ $display }}</span>
                                <template x-if="selected === '{{ $value }}'">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                    </svg>
                                </template>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        @else
            @php
                $paddingRight = ($type === 'password' || isset($icon)) ? 'pr-12' : 'pr-6';
            @endphp

            <div 
                @if($type === 'date') 
                    wire:ignore
                    x-data="{ 
                        val: @entangle($model),
                        instance: null 
                    }" 
                    x-init="
                        instance = flatpickr($refs.input, { 
                            dateFormat: 'Y-m-d', 
                            altInput: true, 
                            altFormat: 'd F Y',
                            allowInput: true,
                            static: true, 
                            position: 'below right',
                            altInputClass: $refs.input.className,
                            defaultDate: val,
                            onChange: (selectedDates, dateStr) => { 
                                val = dateStr; 
                            }
                        });

                        $watch('val', value => {
                            if (instance) {
                                if (!value) instance.clear();
                                else instance.setDate(value, false);
                            }
                        });
                    " 
                @endif
                class="w-full relative [&_.flatpickr-wrapper]:w-full"
            >
                <input
                    x-ref="input"
                    type="{{ $type === 'date' ? 'text' : $type }}"
                    
                    @if($type === 'password')
                        :type="show ? 'text' : 'password'"
                    @endif

                    placeholder="{{ $placeholder }}"
                    
                    @if($model && $type !== 'date') 
                        wire:model="{{ $model }}" 
                    @endif
                    
                    name="{{ $name }}"
                    {{ $attributes->merge(['class' => "w-full px-6 $paddingRight py-3 bg-bg-main border $errorClasses rounded focus:border-secondary-250 outline-none transition-all text-subtext-90 text-web-body-small placeholder:text-subtext-90/50 cursor-pointer"]) }}
                >

                @if(isset($icon) && $type !== 'password')
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-subtext-90">
                        {{ $icon }}
                    </div>
                @endif

                @if($type === 'password')
                    <button type="button" @click="show = !show" class="absolute right-4 top-1/2 -translate-y-1/2 text-subtext-90">
                        <svg x-show="!show" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.978 9.978 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                        <svg x-show="show" style="display: none;" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    </button>
                @endif
            </div>
        @endif
    </div>

    @if($model)
        @error($model)
            <p class="text-xs text-red-500 mt-1 italic">{{ $message }}</p>
        @enderror
    @endif
</div>