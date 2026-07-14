@props([
    'variant' => 'menu', // Pilihan: 'info', 'menu', 'toggle', 'dropdown'
    'label',
    'value'   => null,
    'danger'  => false,
    'options' => [],     // Untuk variant 'dropdown': array of ['code', 'name', 'cc']
    'current' => null,   // Untuk variant 'dropdown': kode bahasa yang aktif
    'isOn'    => false,
])

@if($variant === 'info')
    {{-- For Profile Data --}}
    <div class="min-w-0" title="{{ $value ?? __('None') }}">
        <div class="text-web-body-small text-[14px] text-text-80 mb-1/2 truncate">{{ $label }}</div>
        <div class="text-web-body-small text-[14px] text-subtext-90 truncate">{{ $value ?? __('None') }}</div>
    </div>

@elseif($variant === 'dropdown')
    {{-- Language Selector Dropdown with search --}}
    @php
        $activeOption = collect($options)->firstWhere('code', $current) ?? ($options[0] ?? null);
        $wireAction   = $attributes->get('wire:change', 'saveLanguage');
    @endphp

    <div
        x-data="{
            open: false,
            search: '',
            options: {{ Illuminate\Support\Js::from($options) }},
            get filtered() {
                if (!this.search.trim()) return this.options;
                const q = this.search.toLowerCase();
                return this.options.filter(o =>
                    o.name.toLowerCase().includes(q) || o.code.toLowerCase().includes(q)
                );
            }
        }"
        @click.away="open = false"
        @keydown.escape="open = false"
        class="relative w-full"
    >
        {{-- Trigger row --}}
        <button
            type="button"
            @click="open = !open"
            class="w-full flex items-center justify-between py-3 px-4 -mx-4 transition-colors rounded-xl group hover:bg-brand-50 focus:outline-none"
        >
            <div class="flex items-center gap-4 text-web-body-small text-[14px] text-text-80">
                @if(isset($icon))
                    <div class="text-subtext-90">{{ $icon }}</div>
                @endif
                {{ $label }}
            </div>

            {{-- Active language pill --}}
            <div class="flex items-center gap-2">
                @if($activeOption)
                    <div class="flex items-center gap-2 px-2.5 py-1 rounded-full bg-brand-10 border border-brand-150 group-hover:bg-brand-50 transition-colors">
                        {{-- Circle flag --}}
                        <div class="w-5 h-5 rounded-full overflow-hidden border border-brand-150 shrink-0">
                            <img
                                src="https://flagcdn.com/w40/{{ $activeOption['cc'] }}.png"
                                alt="{{ $activeOption['name'] }}"
                                class="w-full h-full object-cover"
                            >
                        </div>
                        <span class="text-[13px] font-medium text-text-80 whitespace-nowrap">{{ $activeOption['name'] }}</span>
                    </div>
                @endif
                <svg
                    class="w-4 h-4 text-subtext-90 group-hover:text-text-100 transition-transform duration-200"
                    :class="open ? 'rotate-180' : 'rotate-0'"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-200 origin-top"
            x-transition:enter-start="opacity-0 scale-y-75"
            x-transition:enter-end="opacity-100 scale-y-100"
            x-transition:leave="transition ease-in duration-150 origin-top"
            x-transition:leave-start="opacity-100 scale-y-100"
            x-transition:leave-end="opacity-0 scale-y-75"
            class="absolute right-0 top-full mt-1 w-64 bg-card-bg border border-card-border rounded-xl shadow-lg z-50 overflow-hidden"
        >
            {{-- Search input --}}
            <div class="p-2 border-b border-card-border">
                <div class="relative">
                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-subtext-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        x-model="search"
                        type="text"
                        placeholder="{{ __('Search language…') }}"
                        @click.stop
                        class="w-full pl-8 pr-3 py-1.5 text-[13px] bg-brand-10 border border-brand-150 rounded-lg outline-none focus:ring-1 focus:ring-secondary-150 text-text-80 placeholder-subtext-90"
                        x-ref="searchInput"
                        x-init="$watch('open', v => {
                            if (v) {
                                $nextTick(() => {
                                    $refs.searchInput.focus();
                                    const el = $refs.list.querySelector('.bg-brand-50');
                                    if (el) el.scrollIntoView({ block: 'nearest' });
                                });
                            }
                        })"
                    >
                </div>
            </div>

            {{-- Scrollable list --}}
            <div class="overflow-y-auto max-h-52 py-1 custom-scrollbar" x-ref="list">
                <template x-for="option in filtered" :key="option.code">
                    <button
                        type="button"
                        @click="$wire.{{ $wireAction }}(option.code); open = false; search = ''"
                        class="w-full flex items-center gap-3 px-3 py-2 hover:bg-brand-50 transition-colors text-left group"
                        :class="option.code === {{ Illuminate\Support\Js::from($current) }} ? 'bg-brand-50' : ''"
                    >
                        {{-- Circle flag --}}
                        <div class="w-7 h-7 rounded-full border border-brand-150 overflow-hidden shrink-0">
                            <img
                                :src="`https://flagcdn.com/w40/${option.cc}.png`"
                                :srcset="`https://flagcdn.com/w80/${option.cc}.png 2x`"
                                :alt="option.name"
                                class="w-full h-full object-cover"
                            >
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-[13px] font-medium text-text-80 group-hover:text-text-100 truncate" x-text="option.name"></div>
                            <div class="text-[11px] text-subtext-90 uppercase tracking-wider" x-text="option.code"></div>
                        </div>
                        {{-- Active checkmark --}}
                        <svg
                            x-show="option.code === {{ Illuminate\Support\Js::from($current) }}"
                            class="w-4 h-4 text-secondary-200 shrink-0"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                </template>

                {{-- Empty state --}}
                <div x-show="filtered.length === 0" class="px-4 py-3 text-[13px] text-subtext-70 text-center">
                    {{ __('No languages found') }}
                </div>
            </div>
        </div>
    </div>

@else
    {{-- Views 2 & 3: For Menu & Toggle Buttons --}}
    @php
        $textColor = $danger ? 'text-red-500' : 'text-text-80';
        $iconColor = $danger ? 'text-red-500' : 'text-subtext-90';
        $baseClass = 'w-full flex items-center justify-between py-3 px-4 -mx-4 transition-colors rounded-xl group hover:bg-brand-50 focus:outline-none';
        $chevron   = $danger ? 'text-red-400 group-hover:text-red-600' : 'text-subtext-90 group-hover:text-text-100';
    @endphp

    <button
        type="button"
        @if($variant === 'toggle' && !$attributes->has('x-data'))
            x-data="{ isOn: {{ $isOn ? 'true' : 'false' }} }"
        @endif
        @if($variant === 'toggle' && !$attributes->has('@click') && !$attributes->has('x-on:click') && !$attributes->has('wire:click'))
            @click="isOn = !isOn"
        @endif
        {{ $attributes->merge(['class' => $baseClass]) }}
    >
        <div class="flex items-center gap-4 text-web-body-small text-[14px] {{ $textColor }}">
            @if(isset($icon))
                <div class="{{ $iconColor }}">
                    {{ $icon }}
                </div>
            @endif
            {{ $label }}
        </div>

        @if($variant === 'toggle')
            <div
                class="w-11 h-6 rounded-full relative shadow-inner transition-colors duration-300 border border-brand-150"
                :class="isOn ? 'bg-secondary-300 border-secondary-300' : 'bg-brand-100 group-hover:bg-brand-150'"
            >
                <div
                    class="w-5 h-5 bg-white rounded-full shadow absolute top-0.5 left-0.5 transition-transform duration-300"
                    :class="isOn ? 'translate-x-5' : 'translate-x-0'"
                ></div>
            </div>
        @else
            <svg class="w-4 h-4 {{ $chevron }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        @endif
    </button>
@endif