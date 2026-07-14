@props(['status' => 'In Progress'])

@if($status === 'In Progress')
    <svg {{ $attributes->merge(['class' => 'w-3.5 h-3.5']) }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
@elseif($status === 'Completed')
    <svg {{ $attributes->merge(['class' => 'w-3.5 h-3.5']) }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
    </svg>
@endif