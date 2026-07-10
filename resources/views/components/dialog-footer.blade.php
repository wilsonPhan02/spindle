@props([
    'message',
    'cancelText' => 'No, leave it',
    'confirmText' => 'Yes, change it!',
    'confirmType' => 'submit'
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-5']) }}>
    <p class="text-web-body-small text-text-80 mt-4 text-center">
        {{ __($message) }}
    </p>

    <div class="flex gap-4 w-full max-w-2xl mx-auto">
        <x-button 
            @click="show = false" 
            variant="secondary" 
            class="flex-1"
        >
            {{ __($cancelText) }}
        </x-button>

        <x-button 
            type="{{ $confirmType }}" 
            variant="primary" 
            class="flex-1"
        >
            {{ __($confirmText) }}
        </x-button>
    </div>
</div>