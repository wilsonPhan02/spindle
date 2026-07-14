@props([
    'type' => 'button',
    'bg' => null,
    'text' => null,
    'size' => 'py-4 px-6',
    'variant' => null
])

@php
    $presets = [
        'secondary' => 'bg-brand-100 text-text-80 hover:bg-brand-150',
        'primary'   => 'bg-secondary-300 text-subtext-60 hover:bg-[#634735] shadow-lg',
    ];

    $colorClasses = $presets[$variant] ?? '';

    if (!$variant) {
        $bgColor = $bg ?? 'bg-brand-100';
        $textColor = $text ?? 'text-text-80';
        $colorClasses = "$bgColor $textColor";
    }
@endphp

<button 
    type="{{ $type }}"
    {{ $attributes->merge([
        'class' => "rounded-xl text-web-body-small font-semibold transition-all duration-200 focus:outline-none disabled:opacity-50 flex items-center justify-center gap-2 $size $colorClasses"
    ]) }}
>
    {{ $slot }}
</button>