@props([
    'size' => 'w-6 h-6',
    'rotate' => '0',
    'color' => 'text-current',
])

<svg
    {{ $attributes->merge(['class' => $size.' '.$color]) }}
    style="transform: rotate({{ $rotate }}deg);"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    viewBox="0 0 24 24"
    xmlns="http://www.w3.org/2000/svg"
>
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
</svg>