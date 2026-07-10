@props(['items'])

<header class="flex justify-between items-center mb-8 lg:mb-10 w-full">
    <div class="flex items-center gap-2 lg:gap-3 text-[16px] lg:text-[18px] text-[#7A7A7A] flex-1 min-w-0 pr-4">
        @foreach($items as $index => $item)
            @php
                $isTruncated = isset($item['truncate']) && $item['truncate'];
                $textClasses = $isTruncated ? 'truncate max-w-[120px] lg:max-w-[200px] shrink' : 'shrink-0 whitespace-nowrap';
            @endphp

            @if(isset($item['url']) && $item['url'])
                <a href="{{ $item['url'] }}" wire:navigate class="hover:text-[#8C7558] transition-colors block {{ $textClasses }}" title="{{ __($item['label']) }}">
                    {{ __($item['label']) }}
                </a>
            @else
                <span class="text-[#2C2C2C] font-semibold block {{ $textClasses }}" title="{{ __($item['label']) }}">
                    {{ __($item['label']) }}
                </span>
            @endif

            @if(!$loop->last)
                <svg class="w-3.5 h-3.5 lg:w-4 lg:h-4 shrink-0 text-[#7A7A7A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            @endif
        @endforeach
    </div>
    <a href="{{ url('/') }}" class="shrink-0 transition-opacity hover:opacity-80">
        <x-logo class="h-8 w-auto text-[#2C2C2C]" />
    </a>
</header>
