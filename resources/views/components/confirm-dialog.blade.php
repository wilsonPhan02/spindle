@props([
    'eventName',                        
    'title',                            
    'description',     
    'confirmText',     
    'cancelText' => 'No, Stay here',    
    'submitAction' => null,    
    'dispatchAction' => null,
    'iconColor' => 'text-danger-100', 
    'iconBg' => 'bg-danger-100/10',
    'btnColor' => 'bg-danger-100 hover:bg-red-600',
    'showCancel' => true
])

<div 
    x-data="{ show: false, itemId: null }" 
    @keydown.escape.window="show = false"
    x-on:{{ $eventName }}.window="show = true; itemId = $event.detail?.id || null"
    x-show="show" 
    style="display: none;"
    class="fixed inset-0 z-[100] flex items-center justify-center bg-text-80/75 backdrop-blur-[1.5px]"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
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
        <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full {{ $iconBg }}">
            <div class="flex items-center justify-center {{ $iconColor }}">
                {{ $icon ?? '' }}
            </div>
        </div>

        <div class="flex flex-col w-full gap-5">
            <h3 class="text-app-heading-1 text-text-80">{{ $title }}</h3>
            <p class="text-app-subfeature text-text-80 px-3">{!! $description !!}</p>
        </div>

        <div class="flex gap-4 w-full justify-center">
            @if($showCancel)
                <button 
                    @click="show = false" 
                    class="flex-1 py-2 px-4 rounded-lg border border-card-border text-text-80 text-web-body-small font-semibold hover:bg-card-hover transition-colors"
                >
                    {{ __($cancelText) }}
                </button>
            @endif
            <button 
                @click="
                    const action = '{{ $submitAction ?? '' }}';
                    const dispatchEventName = '{{ $dispatchAction ?? '' }}';
                    if (action) {
                        itemId ? $wire.call(action, itemId) : $wire.call(action);
                    }
                    if (dispatchEventName) {
                        $dispatch(dispatchEventName, { id: itemId });
                    }
                    show = false;
                "
                class="flex-1 py-3 px-4 rounded-lg text-bg-main transition-colors text-web-body-small font-semibold {{ $btnColor }}"
            >
                {{ __($confirmText) }}
            </button>
        </div>
    </div>
</div>
