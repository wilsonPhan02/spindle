<?php

use Livewire\Volt\Component;

new class extends Component {
}; ?>

<div 
    x-data="{ 
        show: false,
        canAccept: false,
        checkScroll(el) {
            if (!el) return;
            // Toleransi scroll hingga 20px
            if (el.scrollHeight <= el.clientHeight || el.scrollHeight - el.scrollTop <= el.clientHeight + 20) {
                this.canAccept = true;
            }
        },
        init() {
            this.$watch('show', val => {
                if (val) {
                    document.body.classList.add('overflow-hidden');
                } else {
                    document.body.classList.remove('overflow-hidden');
                }
            });
        }
    }" 
    x-show="show"
    @open-terms-dialog.window="show = true; canAccept = false; $nextTick(() => { checkScroll($refs.scrollContainer) })"
    @close-modal.window="show = false"
    style="display: none;"
    class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 backdrop-blur-sm"
>
    <div 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="bg-brand-10 rounded-2xl border-2 border-brand-150 shadow-2xl w-full max-w-4xl max-h-[85vh] flex flex-col overflow-hidden mx-4"
    >
        <div class="px-8 py-6 border-b border-brand-150 flex items-center bg-brand-50">
            <h2 class="text-app-heading-1 text-text-100">{{ __('Terms & Conditions') }}</h2>
        </div>

        <div x-ref="scrollContainer" @scroll="checkScroll($event.target)" class="px-8 py-6 overflow-y-auto overscroll-contain custom-scrollbar flex-1 min-h-0 text-left">
            @include('partials.terms.terms-' . app()->getLocale())
        </div>

        <div class="px-8 py-5 border-t border-brand-150 bg-brand-50 flex justify-end">
            <x-button variant="primary" @click="show = false" class="px-10" x-bind:disabled="!canAccept" x-bind:class="!canAccept ? 'cursor-not-allowed' : ''">
                {{ __('I Understand') }}
            </x-button>
        </div>
    </div>
</div>
