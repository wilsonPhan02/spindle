@props([
    'eventName',                        
    'title',                            
    'description',     
    'confirmText',     
    'cancelText' => 'No, Stay here',    
    'submitAction',    
    'iconColor' => 'text-danger-100', 
    'iconBg' => 'bg-danger-100/10',
    'btnColor' => 'bg-danger-100 hover:bg-red-600'
])

<div 
    x-data="{ show: false }" 
    @keydown.escape.window="show = false"
    x-on:{{ $eventName }}.window="show = true"
    x-show="show" 
    style="display: none;"
    class="fixed inset-0 z-50 flex items-center justify-center bg-text-80/75 backdrop-blur-[1.5px]"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <!-- Box Pop-up -->
    <div 
        @click.away="show = false"
        class="flex flex-col bg-white rounded-2xl shadow-xl w-full max-w-md px-12 py-8 text-center gap-8"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        <!-- Icon Bulat -->
        <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full {{ $iconBg }}">
            <div class="flex items-center justify-center {{ $iconColor }}">
                {{ $icon }}
            </div>
        </div>

        <!-- Teks -->
        <div class="flex flex-col w-full gap-5">
            <h3 class="text-app-heading-1 text-text-80">{{ $title }}</h3>
            <p class="text-app-subfeature text-text-80 px-3">{{ $description }}</p>
        </div>

        <!-- Tombol -->
        <div class="flex gap-4 w-full justify-center">
            <!-- Tombol Batal -->
            <button 
                @click="show = false" 
                class="flex-1 py-3 px-4 rounded-lg bg-brand-100 text-text-80 text-app-feature hover:bg-brand-150 transition-colors"
            >
                {{ $cancelText }}
            </button>
            <!-- Tombol Aksi Utama (Livewire) -->
            <button 
                wire:click="{{ $submitAction }}" 
                class="flex-1 py-3 px-4 rounded-lg text-subtext-60 transition-colors text-app-feature {{ $btnColor }}"
            >
                {{ $confirmText }}
            </button>
        </div>
    </div>
</div>