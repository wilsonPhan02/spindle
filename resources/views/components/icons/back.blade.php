@props([
    'size' => 'w-5 h-5',
    'color' => 'text-current',
])

<svg
    {{ $attributes->merge(['class' => $size.' '.$color]) }}
    fill="none"
    stroke="currentColor"
    viewBox="0 0 24 24"
    xmlns="http://www.w3.org/2000/svg"
>
    <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="1.5"
        d="M10 19l-7-7m0 0l7-7m-7 7h18"
    />
</svg>