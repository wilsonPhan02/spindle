{{-- Popup info karakter: muncul saat klik karakter (di luar mode tambah relasi) --}}
<div
    x-show="showCharacterInfoPopup"
    style="display: none;"
    @do-delete-character-info.window="deleteCharacterConfirmed()"
    class="fixed inset-0 z-[60] flex items-center justify-center bg-text-80/50 backdrop-blur-[1.5px] p-4 sm:p-6"
>
    <div @click.away="closeCharacterInfo()" class="bg-bg-main border border-brand-150 rounded-xl shadow-xl w-auto overflow-hidden relative max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-2rem)] overflow-y-auto">
        <template x-if="infoCharacter">
            <div>
                {{-- X close button --}}
                <button
                    @click="closeCharacterInfo()"
                    class="absolute top-3 right-3 z-10 w-7 h-7 flex items-center justify-center rounded-full hover:bg-brand-100 transition-colors"
                >
                    <svg class="w-3.5 h-3.5 text-text-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                {{-- Normal view: stacked on mobile, side-by-side on sm+ --}}
                <div class="flex flex-col sm:flex-row sm:max-h-[min(30.5rem,calc(100vh-2rem))]">
                    {{-- Left: image --}}
                    <div class="shrink-0 p-4 sm:p-5 sm:pr-0 flex sm:block">
                        <div class="w-full aspect-[3/4] sm:w-84 bg-brand-100 relative overflow-hidden rounded-xl flex items-center justify-center">
                            <img x-show="infoCharacter.imagePath" :src="infoCharacter.imagePath" draggable="false" class="absolute inset-0 w-full h-full object-cover">
                            <span x-show="!infoCharacter.imagePath" class="text-app-feature text-text-70 font-medium">{{ __('Image Placeholder') }}</span>
                        </div>
                    </div>
                    {{-- Right: content --}}
                    <div class="flex flex-col flex-1 min-w-0 sm:min-h-0 p-5 sm:p-7 sm:w-108 gap-4">
                        <div>
                            <h3 class="text-app-title-1 text-text-80 truncate" :title="infoCharacter.name" x-text="infoCharacter.name"></h3>
                            <p class="text-app-subtitle-1 text-text-60 truncate" >{{ __('Fullname : ') }}<span x-text="infoCharacter.fullName" :title="infoCharacter.fullName"></span></p>
                        </div>
                        
                        <hr class="border-t border-1 border-brand-150 w-full" />
                        
                        <div>
                            <p class="text-app-feature text-text-70 mb-2">{{ __('Tags') }}</p>
                            <div
                                x-data="{
                                    hiddenCount: 0,
                                    calcOverflow() {
                                        const tags = [...this.$el.querySelectorAll('[data-tag-item]')];
                                        if (!tags.length) { this.hiddenCount = 0; return; }
                                        
                                        tags.forEach(t => t.style.display = '');
                                        const baseTop = tags[0].offsetTop;
                                        
                                        let hidden = 0;
                                        tags.forEach(t => {
                                            if (t.offsetTop > baseTop + 5) { 
                                                t.style.display = 'none'; 
                                                hidden++; 
                                            }
                                        });
                                        
                                        this.hiddenCount = hidden;
                                    }
                                }"
                                x-init="$nextTick(() => calcOverflow())"
                                class="flex flex-wrap gap-1.5"
                            >
                                <template x-for="tag in infoCharacter.tags" :key="tag">
                                    <span data-tag-item class="px-4 py-2 rounded-full bg-brand-100 text-app-body-medium text-text-60 truncate min-w-0 max-w-[10rem]" x-text="tag"></span>
                                </template>
                                <span x-show="infoCharacter.tags.length === 0" class="text-app-body-medium text-text-60">{{ __('No tag yet') }}</span>

                                <span
                                    x-show="hiddenCount > 0"
                                    style="display:none;"
                                    class="px-4 py-2 rounded-full bg-brand-150 text-app-body-medium text-text-60 shrink-0 whitespace-nowrap"
                                    x-text="'+' + hiddenCount + ' ' + '{{ __('more') }}'"
                                ></span>
                            </div>
                        </div>
                        <div class="sm:flex-1 sm:min-h-0 flex flex-col">
                            <p class="text-app-feature text-text-70 mb-2 shrink-0">{{ __('Backstory') }}</p>
                            <p
                                class="text-app-body-medium text-text-60 break-words line-clamp-5"
                                x-text="infoCharacter.bio || '{{ __('No Description Yet') }}'"
                            ></p>
                        </div>
                        <div class="flex gap-2.5 mt-2">
                            <button @click="$dispatch('open-delete-character-info-confirm')" class="p-3 rounded-lg cursor-pointer border border-danger-100 text-danger-100 hover:bg-danger-100/10 transition-colors"
                            title="{{ __('Delete Character') }}">
                                <x-icons.delete class="w-5 h-5"/>
                            </button>
                            <button @click="viewCharacterDetail()" class="w-full p-3 cursor-pointer rounded-lg bg-secondary-100 text-app-feature text-bg-main hover:bg-secondary-150 transition-colors">{{ __('Edit Character Detail') }}</button>
                        </div>
                    </div>
                </div>

            </div>
        </template>
    </div>

    <x-confirm-dialog
        eventName="open-delete-character-info-confirm"
        title="{{ __('Delete Character?') }}"
        description='&quot;<span x-text="infoCharacter.fullName"></span>&quot; {{ __("and every relationship involving them will be permanently removed.") }}'
        confirmText="{{ __('Confirm Delete') }}"
        dispatchAction="do-delete-character-info"
    >
        <x-slot:icon>
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
        </x-slot:icon>
    </x-confirm-dialog>
</div>
