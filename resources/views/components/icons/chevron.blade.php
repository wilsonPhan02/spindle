@props([
    'size' => 'w-6 h-6',
    'color' => 'text-text-80',
    'rotate' => '0'
])

<svg 
    {{ $attributes->merge(['class' => "$size $color"]) }} 
    style="transform: rotate({{ $rotate }}deg); transition: transform 0.2s ease;"
    fill="none" 
    stroke="currentColor" 
    viewBox="0 0 24 24"
>
    <path 
        stroke-linecap="round" 
        stroke-linejoin="round" 
        stroke-width="2" 
        d="M9 5l7 7-7 7"
    />
</svg>