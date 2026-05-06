{{-- @props([
    'variant' => 'menu', // Pilihan: 'info', 'menu', 'toggle'
    'label',
    'value' => null
])

@if($variant === 'info')
    <!-- Untuk Data Profile -->
    <div>
        <div class="text-web-body-small text-text-80 mb-1">{{ $label }}</div>
        <div class="text-web-body-small text-subtext-90">{{ $value ?? 'None' }}</div>
    </div>
@else
    <!-- Tampilan 2 & 3: Untuk Tombol Menu & Toggle -->
    @php
        $textColor = 'text-text-80';
        $iconColor = 'text-subtext-90';
        $hoverBg   = 'hover:bg-black/5';
        $chevron   = 'text-subtext-90 group-hover:text-text-100';
    @endphp

    <button {{ $attributes->merge(['class' => "w-full flex items-center justify-between py-3.5 transition-colors rounx  ded-lg -mx-2 px-2 group $hoverBg"]) }}>
        <div class="flex items-center gap-4 text-web-body-small {{ $textColor }}">
            <!-- Slot untuk Ikon SVG -->
            @if(isset($icon))
                <div class="{{ $iconColor }} {{ 'group-hover:text-text-100' }} transition-colors">
                    {{ $icon }}
                </div>
            @endif
            {{ $label }}
        </div>

        @if($variant === 'toggle')
            <!-- Bentuk Saklar Toggle (Dark Mode) -->
            <div class="w-11 h-6 bg-gray-200 rounded-full relative shadow-inner cursor-pointer">
                <div class="w-5 h-5 bg-white rounded-full shadow absolute top-0.5 left-0.5 transition-transform"></div>
            </div>
        @else
            <!-- Bentuk Panah (Menu Biasa) -->
            <svg class="w-4 h-4 {{ $chevron }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        @endif
    </button>
@endif --}}


@props([
    'variant' => 'menu', // Pilihan: 'info', 'menu', 'toggle'
    'label',
    'value' => null
])

@if($variant === 'info')
    <!-- Untuk Data Profile -->
    <div>
        <div class="text-web-body-small text-text-80 mb-1">{{ $label }}</div>
        <div class="text-web-body-small text-subtext-90">{{ $value ?? 'None' }}</div>
    </div>
@else
    <!-- Tampilan 2 & 3: Untuk Tombol Menu & Toggle -->
    @php
        $textColor = 'text-text-80';
        $iconColor = 'text-subtext-90';
        // PERBAIKAN: px-4 & -mx-4 biar hover lebih lebar, rounded-xl biar radius lebih terlihat
        $baseClass = 'w-full flex items-center justify-between py-3.5 px-4 -mx-4 transition-colors rounded-xl group hover:bg-black/5 focus:outline-none';
        $chevron   = 'text-subtext-90 group-hover:text-text-100';
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
            <!-- Bentuk Saklar Toggle (Bisa Diklik) -->
            {{-- Background ganti warna kalau isOn = true --}}
            <div 
                class="w-11 h-6 rounded-full relative shadow-inner transition-colors duration-300"
                :class="isOn ? 'bg-blue-600' : 'bg-gray-200'"
            >
                {{-- Lingkaran putih geser ke kanan kalau isOn = true --}}
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