@props([
    'variant' => 'menu', // Pilihan: 'info', 'menu', 'toggle'
    'label',
    'value' => null,
    'danger' => false
])

@if($variant === 'info')
    <!-- Untuk Data Profile -->
    <div class="min-w-0" title="{{ $value ?? 'None' }}">
        <div class="text-web-body-small text-text-80 mb-1">{{ $label }}</div>
        <div class="text-web-body-small text-subtext-90 truncate">{{ $value ?? 'None' }}</div>
    </div>
@else
    <!-- Tampilan 2 & 3: Untuk Tombol Menu & Toggle -->
    @php
        $textColor = $danger ? 'text-red-500' : 'text-text-80';
        $iconColor = $danger ? 'text-red-500' : 'text-subtext-90';
        $baseClass = 'w-full flex items-center justify-between py-3.5 px-4 -mx-4 transition-colors rounded-xl group hover:bg-black/5 focus:outline-none';
        $chevron   = $danger ? 'text-red-400 group-hover:text-red-600' : 'text-subtext-90 group-hover:text-text-100';
    @endphp

    {{-- Tambahkan Alpine.js x-data jika variant-nya toggle --}}
    <button 
        @if($variant === 'toggle') 
            x-data="{ isOn: false }" 
            @click="isOn = !isOn" 
        @endif
        {{ $attributes->merge(['class' => $baseClass]) }}
    >
        <div class="flex items-center gap-4 text-web-body-small {{ $textColor }}">
            <!-- Slot untuk Ikon SVG -->
            @if(isset($icon))
                <div class="{{ $iconColor }}">
                    {{ $icon }}
                </div>
            @endif
            {{ $label }}
        </div>

        @if($variant === 'toggle')
            <div 
                class="w-11 h-6 rounded-full relative shadow-inner transition-colors duration-300"
                :class="isOn ? 'bg-secondary-300' : 'bg-gray-200'"
            >
                <div 
                    class="w-5 h-5 bg-white rounded-full shadow absolute top-0.5 left-0.5 transition-transform duration-300"
                    :class="isOn ? 'translate-x-5' : 'translate-x-0'"
                ></div>
            </div>
        @else
            <!-- Bentuk Panah (Menu Biasa) -->
            <svg class="w-4 h-4 {{ $chevron }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        @endif
    </button>
@endif