        <div class="bg-brand-10 border border-brand-150 rounded-2xl p-6 flex flex-col gap-6 h-full">
            <div class="flex flex-col gap-3">
                <h3 class="text-app-feature text-text-100">{{ __('Character Image') }}</h3>
                <div x-data="{ 
                        hoverCover: false,
                        isUploading: false,
                        progress: 0,
                        showCropper: false,
                        cropImageUrl: null,
                        cropperInstance: null,
                        clientError: null,
                        
                        cancelCrop() {
                            this.showCropper = false;
                            if (this.cropperInstance) {
                                this.cropperInstance.destroy();
                                this.cropperInstance = null;
                            }
                            this.cropImageUrl = null;
                            if(this.$refs.characterImageInput) this.$refs.characterImageInput.value = null;
                        },
                        
                        applyCrop() {
                            if (!this.cropperInstance) return;
                            
                            const canvas = this.cropperInstance.getCroppedCanvas({
                                width: 600,
                                height: 800
                            });
                            
                            canvas.toBlob((blob) => {
                                const file = new File([blob], 'character.jpg', { type: 'image/jpeg', lastModified: Date.now() });
                                
                                this.cancelCrop();
                                
                                this.isUploading = true;
                                this.progress = 0;
                                
                                @this.upload('newImage', file,
                                    (uploadedFilename) => { this.isUploading = false; },
                                    () => { this.isUploading = false; },
                                    (e) => { this.progress = e.detail.progress; }
                                );
                            }, 'image/jpeg', 0.9);
                        }
                     }"
                     @mouseover="hoverCover = true"
                     @mouseleave="hoverCover = false"
                     class="relative aspect-[3/4] w-full rounded-lg bg-brand-100 overflow-hidden z-10">
                    
                    @if($character->image_path)
                        <img src="{{ Storage::url($character->image_path) }}" class="absolute inset-0 w-full h-full object-cover">
                    @else
                        <div class="absolute inset-0 flex items-center justify-center text-subtext-80 text-app-desc-feature text-center px-4 border border-dashed border-brand-200 rounded-lg">
                            {{ __('No Image (3:4)') }}
                        </div>
                    @endif

                    {{-- Cropper Modal Overlay --}}
                    <div x-show="showCropper" x-cloak class="absolute inset-0 z-50 bg-bg-main flex flex-col rounded-lg overflow-hidden border border-black/10">
                        <div class="flex-1 w-full relative">
                            <img x-ref="cropperImg" :src="cropImageUrl" class="block max-w-full" alt="Crop Preview">
                        </div>
                        <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-2 z-50">
                            <button @click="cancelCrop()" type="button" class="px-4 py-1.5 bg-bg-main/90 backdrop-blur text-text-70 text-[11px] font-bold uppercase tracking-wider rounded-md border border-text-60 hover:bg-bg-main shadow-lg transition-colors">{{ __('Cancel') }}</button>
                            <button @click="applyCrop()" type="button" class="px-4 py-1.5 bg-secondary-100/95 backdrop-blur text-bg-main text-[11px] font-bold uppercase tracking-wider rounded-md shadow-lg border border-secondary-200 hover:bg-secondary-200 transition-colors">{{ __('Save') }}</button>
                        </div>
                    </div>

                    <div x-show="hoverCover && !showCropper" x-transition class="absolute bottom-4 left-4 z-30 flex flex-wrap gap-2">
                        <label class="flex items-center gap-1.5 px-2.5 py-1.5 bg-text-80/95 border border-text-60 rounded-md cursor-pointer hover:bg-text-80 transition-colors shadow-lg">
                            <x-icons.upload class="w-3.5 h-3.5 text-bg-main" />
                            <span class="text-bg-main text-app-desc-feature">{{ __('Upload') }}</span>
                            <input type="file" x-ref="characterImageInput" class="hidden" accept="image/*"
                                @change="
                                    const file = $event.target.files[0];
                                    if (file) {
                                        if (file.size > 5 * 1024 * 1024) {
                                            clientError = 'The selected image is too large. The maximum allowed file size is 5MB.';
                                            $event.target.value = '';
                                        } else {
                                            clientError = null;
                                            
                                            const reader = new FileReader();
                                            reader.onload = (e) => { 
                                                cropImageUrl = e.target.result;
                                                showCropper = true;
                                                
                                                $nextTick(() => {
                                                    if (cropperInstance) cropperInstance.destroy();
                                                    cropperInstance = new Cropper($refs.cropperImg, {
                                                        aspectRatio: 3 / 4,
                                                        viewMode: 1,
                                                        dragMode: 'move',
                                                        background: false,
                                                        guides: false,
                                                        center: true,
                                                        highlight: false,
                                                        cropBoxMovable: false,
                                                        cropBoxResizable: false,
                                                        minCropBoxWidth: 150,
                                                        minCropBoxHeight: 200,
                                                    });
                                                });
                                            };
                                            reader.readAsDataURL(file);
                                        }
                                    }
                                ">
                        </label>

                        @if($character->image_path)
                            <button type="button" wire:click="removeImage" class="flex items-center gap-1.5 px-2.5 py-1.5 bg-text-80/95 border border-text-60 rounded-md cursor-pointer hover:bg-text-80 transition-colors shadow-lg">
                                <x-icons.delete class="w-3.5 h-3.5 text-danger-100" />
                                <span class="text-app-desc-feature text-danger-100">{{ __('Remove') }}</span>
                            </button>
                        @endif
                    </div>

                    {{-- Client-side Error --}}
                    <template x-if="clientError">
                        <div class="absolute inset-x-2 top-2 bg-danger-100/95 text-bg-main text-[11px] font-medium px-2 py-2 rounded shadow-xl z-50 flex items-start gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0 text-bg-main" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <span x-text="clientError" class="flex-1 leading-tight"></span>
                            <button @click="clientError = null" class="shrink-0 p-0.5 hover:bg-black/20 rounded transition-colors" title="{{ __('Dismiss') }}"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                        </div> 
                    </template>

                    {{-- Server-side Error --}}
                    @error('newImage') 
                        <div x-data="{ show: true }" x-show="show" class="absolute inset-x-2 top-2 bg-danger-100/95 text-bg-main text-[11px] font-medium px-2 py-2 rounded shadow-xl z-50 flex items-start gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0 text-bg-main" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <span class="flex-1 leading-tight">{{ $message }}</span>
                            <button @click="show = false" class="shrink-0 p-0.5 hover:bg-black/20 rounded transition-colors" title="{{ __('Dismiss') }}"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                        </div> 
                    @enderror

                    {{-- Progress Overlay --}}
                    <div x-show="isUploading" x-transition class="absolute inset-0 bg-secondary-5/80 backdrop-blur-md z-40 flex flex-col items-center justify-center rounded-lg">
                        <svg class="animate-spin h-8 w-8 text-secondary-200 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <div class="text-secondary-200 font-semibold text-sm">{{ __('Uploading...') }} <span x-text="progress + '%'"></span></div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4 mb-4">
                <h3 class="text-app-feature text-text-100 pb-2 border-b border-brand-150">{{ __('Tags') }}</h3>
                <div
                    x-data="{
                        showNewTagInput: false,
                        tags: @js($tags),
                        newTagName: '',
                        async addTag() {
                            const name = this.newTagName.trim();
                            if (name === '') return;
                            this.showNewTagInput = false;
                            this.newTagName = '';
                            const tempId = 'temp-' + Date.now();
                            this.tags.push({ id: tempId, name });
                            const tag = await this.$wire.call('addTag', name);
                            const idx = this.tags.findIndex(t => t.id === tempId);
                            if (idx !== -1 && tag) this.tags[idx] = tag;
                        },
                        removeTag(id) {
                            this.tags = this.tags.filter(t => t.id !== id);
                            this.$wire.call('removeTag', id);
                        },
                    }"
                    class="flex flex-wrap items-center gap-2"
                >
                    <button
                        x-show="!showNewTagInput"
                        @click="showNewTagInput = true"
                        type="button"
                        class="shrink-0 w-8 h-8 rounded-full bg-brand-100 flex items-center justify-center text-text-70 hover:bg-brand-150 transition-colors"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </button>
                    <div x-show="showNewTagInput" x-cloak class="flex items-center gap-1">
                        <div class="relative shrink-0">
                            <input
                                type="text"
                                x-model="newTagName"
                                x-init="$watch('showNewTagInput', value => { if (value) $nextTick(() => $el.focus()) })"
                                @input="if (newTagName.startsWith(' ')) newTagName = newTagName.replace(/^\s+/, '')"
                                @keydown.enter="addTag()"
                                @keydown.escape="showNewTagInput = false; newTagName = ''"
                                maxlength="20"
                                placeholder="{{ __('New tag...') }}"
                                class="pl-3 pr-11 py-2 rounded-full bg-brand-100 border border-secondary-100 outline-none text-app-body-small text-text-70 w-36"
                            >
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-app-desc-feature text-secondary-100 pointer-events-none" x-text="newTagName.length + '/20'"></span>
                        </div>
                        <button @click="showNewTagInput = false; newTagName = ''" type="button" class="w-5 h-5 rounded-full flex items-center justify-center text-text-60 hover:bg-black/10 hover:text-danger-100 transition-colors">
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <template x-for="tag in tags" :key="tag.id">
                        <span class="flex items-center gap-2 pl-4 pr-2 py-1.5 rounded-full bg-brand-100 text-app-body-small text-text-80 max-w-50">
                            <div class="truncate flex-1" x-text="tag.name"></div>
                            <button type="button" @click="removeTag(tag.id)" class="shrink-0 w-4 h-4 rounded-full flex items-center justify-center text-text-60 hover:bg-black/10 hover:text-danger-100 transition-colors">
                                <svg class="w-2 h-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </span>
                    </template>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between pb-2 border-b border-brand-150">
                    <h3 class="text-app-feature text-text-100">{{ __('Relationship') }}</h3>
                    <button wire:click="$set('showAddRelation', true)" type="button" class="w-6 h-6 rounded-full bg-brand-100 flex items-center justify-center text-text-70 hover:bg-brand-150 transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </button>
                </div>

                @if($showAddRelation)
                    @php
                        $relatedTypesByChar = collect($relationships)
                            ->filter(fn ($r) => $r['otherId'])
                            ->groupBy('otherId')
                            ->map(fn ($group) => $group->pluck('typeId')->values()->all())
                            ->all();
                    @endphp
                    <div
                        class="flex flex-col gap-2 p-3 rounded-lg bg-brand-100/60"
                        x-data="{
                            charOpen: false,
                            typeOpen: false,
                            selectedChar: null,
                            selectedType: null,
                            relatedTypesByChar: @js($relatedTypesByChar),
                            characters: @js($otherCharacters),
                            types: @js($relationshipTypes),
                            selectChar(char) {
                                this.selectedChar = char;
                                this.charOpen = false;
                                $wire.set('newRelationTargetId', char.id);
                                const usedTypes = this.relatedTypesByChar[char.id] || [];
                                if (this.selectedType && usedTypes.includes(this.selectedType.id)) {
                                    this.selectedType = null;
                                    $wire.set('newRelationTypeId', null);
                                }
                            },
                            isTypeUsed(typeId) {
                                if (!this.selectedChar) return false;
                                return (this.relatedTypesByChar[this.selectedChar.id] || []).includes(typeId);
                            },
                            selectType(type) {
                                if (this.isTypeUsed(type.id)) return;
                                this.selectedType = type;
                                this.typeOpen = false;
                                $wire.set('newRelationTypeId', type.id);
                            },
                        }"
                        @type-selected.window="
                            const type = $event.detail.type;
                            if (!types.find(t => t.id === type.id)) types.push(type);
                            selectType(type);
                        "
                        @relation-type-deleted.window="
                            const { typeId } = $event.detail;
                            types = types.filter(t => t.id !== typeId);
                            if (selectedType && selectedType.id === typeId) {
                                selectedType = null;
                                $wire.set('newRelationTypeId', null);
                            }
                        "
                    >
                        {{-- Character dropdown --}}
                        <div class="relative" @click.away="charOpen = false">
                            <button type="button" @click="charOpen = !charOpen"
                                class="w-full px-3 py-2 bg-bg-main border border-brand-150 rounded-lg text-app-desc-feature text-left flex items-center justify-between gap-2 outline-none transition-colors hover:border-secondary-100"
                                :class="selectedChar ? 'text-text-80' : 'text-subtext-70'"
                            >
                                <span x-text="selectedChar ? selectedChar.name : '{{ __('Select character...') }}'"></span>
                                <svg class="w-3 h-3 text-text-60 shrink-0 transition-transform duration-150" :class="charOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="charOpen" x-cloak
                                class="absolute z-20 w-full mt-1 bg-bg-main border border-brand-150 rounded-lg shadow-md overflow-hidden max-h-40 overflow-y-auto custom-scrollbar">
                                <template x-for="char in characters" :key="char.id">
                                    <div
                                        @click="selectChar(char)"
                                        class="px-3 py-2 text-app-desc-feature text-text-80 flex items-center justify-between hover:bg-brand-100 cursor-pointer"
                                    >
                                        <span x-text="char.name"></span>
                                    </div>
                                </template>
                                <div x-show="characters.length === 0" class="px-3 py-2 text-app-desc-feature text-subtext-70 italic">{{ __('No other characters') }}</div>
                            </div>
                        </div>

                        {{-- Relationship type dropdown --}}
                        <div class="relative" @click.away="typeOpen = false">
                            <button type="button" @click="typeOpen = !typeOpen"
                                class="w-full px-3 py-2 bg-bg-main border border-brand-150 rounded-lg text-app-desc-feature text-left flex items-center justify-between gap-2 outline-none transition-colors hover:border-secondary-100"
                            >
                                <span x-show="!selectedType" class="text-subtext-70">{{ __('Select type...') }}</span>
                                <span x-show="selectedType" x-cloak
                                    class="px-2.5 py-0.5 rounded-full text-app-desc-feature"
                                    :style="selectedType ? `background-color: ${selectedType.bgColor}; color: ${selectedType.textColor}` : ''"
                                    x-text="selectedType ? selectedType.name : ''">
                                </span>
                                <svg class="w-3 h-3 text-text-60 shrink-0 transition-transform duration-150 ml-auto" :class="typeOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="typeOpen" x-cloak
                                class="absolute z-20 w-full mt-1 bg-bg-main border border-brand-150 rounded-lg shadow-md overflow-hidden max-h-48 overflow-y-auto custom-scrollbar">
                                <template x-for="type in types" :key="type.id">
                                    <div @click="selectType(type)"
                                        class="px-3 py-2 flex items-center"
                                        :class="isTypeUsed(type.id) ? 'opacity-40 cursor-not-allowed' : 'hover:bg-brand-100 cursor-pointer'"
                                    >
                                        <span
                                            class="px-2.5 py-1 rounded-full text-app-desc-feature font-semibold"
                                            :style="`background-color: ${type.bgColor}; color: ${type.textColor}`"
                                            x-text="type.name">
                                        </span>
                                    </div>
                                </template>
                                <div x-show="types.length === 0" class="px-3 py-2 text-app-desc-feature text-subtext-70 italic">{{ __('No types yet') }}</div>
                                <div class="border-t border-brand-150">
                                    <div @click="typeOpen = false; window.dispatchEvent(new CustomEvent('open-relation-type-popup', { detail: { relationId: null, charFromName: '{{ $character->nick_name }}', charToName: selectedChar ? selectedChar.name : null, usedTypeIds: selectedChar ? (relatedTypesByChar[selectedChar.id] || []) : [] } }))"
                                        class="px-3 py-2 flex items-center gap-2 text-app-desc-feature font-semibold text-secondary-200 hover:bg-brand-100 cursor-pointer">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                        {{ __('Add New Relation') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tipe baru dari popup → auto-pilih di dropdown --}}
                        {{-- Tipe dihapus dari popup → singkirkan dari list --}}

                        {{-- Action buttons --}}
                        <div class="flex gap-2">
                            <button wire:click="$set('showAddRelation', false)" type="button"
                                class="flex-1 py-2 rounded-md border border-secondary-50 text-app-desc-feature text-text-80 hover:bg-brand-100 transition-colors">
                                {{ __('Cancel') }}
                            </button>
                            <button wire:click="addRelationship" type="button"
                                :disabled="!selectedChar || !selectedType"
                                :class="(!selectedChar || !selectedType) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-brand-200'"
                                class="flex-1 py-2 rounded-md bg-brand-150 text-app-desc-feature text-text-80 transition-colors">
                                {{ __('Add') }}
                            </button>
                        </div>
                    </div>
                @endif

                <div class="flex flex-col gap-2"
                    x-data
                    @relation-saved.window="$wire.call('refreshRelationships')"
                    @relation-deleted.window="$wire.call('refreshRelationships')"
                    @relation-type-deleted.window="$wire.call('refreshRelationships')"
                >
                    @forelse($relationships as $relationship)
                        @php
                            $usedTypeIdsForOther = collect($relationships)->where('otherId', $relationship['otherId'])->pluck('typeId')->values()->all();
                        @endphp
                        <div class="grid grid-cols-[1fr_auto_1fr_auto] items-center gap-x-2 text-text-80 text-app-body-small w-full">
                            <div class="bg-brand-100 px-3 py-1.5 rounded-md truncate cursor-pointer hover:bg-brand-150 transition-colors"
                                @click="window.dispatchEvent(new CustomEvent('open-edit-relation-popup', { detail: { relationId: '{{ $relationship['id'] }}', typeId: '{{ $relationship['typeId'] }}', charFromName: '{{ $character->nick_name }}', charToName: '{{ $relationship['otherName'] }}', usedTypeIds: @js($usedTypeIdsForOther) } }))">
                                {{ $relationship['otherName'] }}
                            </div>

                            <span class="font-medium text-center">:</span>

                            <div class="px-3 py-1.5 rounded-md text-center truncate cursor-pointer hover:opacity-80 transition-opacity"
                                style="background-color: {{ $relationship['bgColor'] }}; color: {{ $relationship['textColor'] }};"
                                @click="window.dispatchEvent(new CustomEvent('open-edit-relation-popup', { detail: { relationId: '{{ $relationship['id'] }}', typeId: '{{ $relationship['typeId'] }}', charFromName: '{{ $character->nick_name }}', charToName: '{{ $relationship['otherName'] }}', usedTypeIds: @js($usedTypeIdsForOther) } }))">
                                {{ $relationship['typeName'] }}
                            </div>

                            <button wire:click="deleteRelationship('{{ $relationship['id'] }}')" type="button" class="w-5 h-5 rounded-full flex items-center justify-center text-text-60 hover:bg-black/10 hover:text-danger-100 transition-colors shrink-0">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    @empty
                        <p class="text-app-desc-feature text-subtext-90">{{ __('No relationships yet.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
