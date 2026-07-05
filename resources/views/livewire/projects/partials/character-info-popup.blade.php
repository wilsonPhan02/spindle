{{-- Popup info karakter: muncul saat klik karakter (di luar mode tambah relasi) --}}
<div
    x-show="showCharacterInfoPopup"
    style="display: none;"
    class="fixed inset-0 z-50 flex items-center justify-center bg-text-80/50 backdrop-blur-[1.5px] p-4 sm:p-6"
>
    <div @click.away="closeCharacterInfo()" class="bg-bg-main border border-brand-150 rounded-xl shadow-xl w-auto overflow-hidden relative max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-2rem)] overflow-y-auto">
        <template x-if="infoCharacter">
            <div x-data="{ confirmingDelete: false }">
                {{-- X close button --}}
                <button
                    @click="closeCharacterInfo()"
                    class="absolute top-3 right-3 z-10 w-7 h-7 flex items-center justify-center rounded-full hover:bg-brand-100 transition-colors"
                >
                    <svg class="w-3.5 h-3.5 text-text-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                {{-- Normal view: stacked on mobile, side-by-side on sm+ --}}
                <div x-show="!confirmingDelete" class="flex flex-col sm:flex-row sm:max-h-[min(30.5rem,calc(100vh-2rem))]">
                    {{-- Left: image --}}
                    <div class="shrink-0 p-4 sm:p-5 sm:pr-0 flex sm:block">
                        <div class="w-full aspect-[3/4] sm:w-84 bg-brand-100 relative overflow-hidden rounded-xl flex items-center justify-center">
                            <img x-show="infoCharacter.imagePath" :src="infoCharacter.imagePath" draggable="false" class="absolute inset-0 w-full h-full object-cover">
                            <span x-show="!infoCharacter.imagePath" class="text-app-feature text-text-70 font-medium">Image Placeholder</span>
                        </div>
                    </div>
                    {{-- Right: content --}}
                    <div class="flex flex-col flex-1 min-w-0 sm:min-h-0 p-5 sm:p-7 sm:w-108 gap-4">
                        <div>
                            <h3 class="text-app-title-1 text-text-80 truncate" :title="infoCharacter.fullName" x-text="infoCharacter.fullName"></h3>
                            <p class="text-app-subtitle-1 text-text-60 truncate" >Nickname : <span x-text="infoCharacter.name" :title="infoCharacter.name"></span></p>
                        </div>
                        
                        <hr class="border-t border-1 border-brand-150 w-full" />
                        
                        <div>
                            <p class="text-app-feature text-text-70 mb-2">Tags</p>
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
                                <span x-show="infoCharacter.tags.length === 0" class="text-app-body-medium text-text-60">No tag yet</span>

                                <span
                                    x-show="hiddenCount > 0"
                                    style="display:none;"
                                    class="px-4 py-2 rounded-full bg-brand-150 text-app-body-medium text-text-60 shrink-0 whitespace-nowrap"
                                    x-text="'+' + hiddenCount + ' more'"
                                ></span>
                            </div>
                        </div>
                        <div class="sm:flex-1 sm:min-h-0 flex flex-col">
                            <p class="text-app-feature text-text-70 mb-2 shrink-0">Backstory</p>
                            <p
                                class="text-app-body-medium text-text-60 break-words line-clamp-5"
                                x-text="infoCharacter.bio || 'No Description Yet'"
                            ></p>
                        </div>
                        <div class="flex gap-2.5 mt-2">
                            <button @click="confirmingDelete = true" class="p-3 rounded-lg cursor-pointer border border-danger-100 text-danger-100 hover:bg-danger-100/10 transition-colors"
                            title="Delete Character">
                                <x-icons.delete class="w-5 h-5"/>
                            </button>
                            <button @click="viewCharacterDetail()" class="w-full p-3 cursor-pointer rounded-lg bg-brand-150 text-app-feature text-text-80 hover:bg-brand-200 transition-colors">Edit Character Detail</button>
                        </div>
                    </div>
                </div>

                {{-- Confirm delete view --}}
                <div x-show="confirmingDelete" style="display:none;" class="flex flex-col gap-5 p-6">
                    <div class="text-center sm:w-96">
                        <h3 class="text-app-heading-2 text-text-80">Delete Character?</h3>
                        <p class="text-app-desc-feature text-text-70 mt-2">"<span x-text="infoCharacter.fullName"></span>" and every relationship involving them will be permanently removed.</p>
                    </div>
                    <div class="flex gap-3">
                        <button @click="confirmingDelete = false" class="flex-1 py-2.5 rounded-lg border border-brand-200 text-app-feature text-text-80 hover:bg-brand-100 transition-colors">Cancel</button>
                        <button @click="deleteCharacterConfirmed()" class="flex-1 py-2.5 rounded-lg bg-danger-100 text-app-feature text-bg-main hover:bg-danger-100/90 transition-colors">Confirm Delete</button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
