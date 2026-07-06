<?php

use Livewire\Volt\Component;
use App\Models\Project;
use App\Models\Relationship;
use App\Models\RelationshipType;
use Livewire\Attributes\Renderless;

new class extends Component {
    public Project $project;
    public array $relationshipTypes = [];

    public function mount(Project $project): void {
        $this->project = $project;
        $this->loadTypes();
    }

    private function loadTypes(): void {
        $this->relationshipTypes = $this->project->relationshipTypes()->get()->map(fn (RelationshipType $type) => [
            'id'        => $type->relationship_type_id,
            'name'      => $type->name,
            'textColor' => $type->text_color,
            'bgColor'   => $type->bg_color,
        ])->all();
    }

    #[Renderless]
    public function createRelationshipType(string $name, string $textColor, string $bgColor): array {
        $name = trim($name);

        $existing = $this->project->relationshipTypes()
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();

        if ($existing) {
            return ['id' => $existing->relationship_type_id, 'name' => $existing->name, 'textColor' => $existing->text_color, 'bgColor' => $existing->bg_color];
        }

        $type = $this->project->relationshipTypes()->create([
            'name'       => $name,
            'text_color' => $textColor,
            'bg_color'   => $bgColor,
        ]);

        return ['id' => $type->relationship_type_id, 'name' => $type->name, 'textColor' => $type->text_color, 'bgColor' => $type->bg_color];
    }

    #[Renderless]
    public function deleteRelationshipType(string $typeId): void {
        $this->project->relationshipTypes()->where('relationship_type_id', $typeId)->delete();
    }

    #[Renderless]
    public function updateRelationship(string $relationshipId, string $typeId): void {
        $characterIds = $this->project->characters()->pluck('character_id');
        $relationship = Relationship::where('relationship_id', $relationshipId)
            ->where(fn ($q) => $q->whereIn('from_id', $characterIds)->orWhereIn('to_id', $characterIds))
            ->first();

        if (! $relationship) {
            return;
        }

        $duplicate = Relationship::where('relationship_id', '!=', $relationshipId)
            ->where('relationship_type_id', $typeId)
            ->where(function ($query) use ($relationship) {
                $query->where(fn ($q) => $q->where('from_id', $relationship->from_id)->where('to_id', $relationship->to_id))
                    ->orWhere(fn ($q) => $q->where('from_id', $relationship->to_id)->where('to_id', $relationship->from_id));
            })
            ->exists();

        if ($duplicate) {
            return;
        }

        $relationship->update(['relationship_type_id' => $typeId]);
    }

    #[Renderless]
    public function deleteRelationship(string $relationshipId): void {
        $characterIds = $this->project->characters()->pluck('character_id');
        Relationship::where('relationship_id', $relationshipId)
            ->where(fn ($q) => $q->whereIn('from_id', $characterIds)->orWhereIn('to_id', $characterIds))
            ->delete();
    }
}; ?>

<div
    x-data="{
        showPopup: false,
        editingRelationId: null,
        editingRelationSavedTypeId: null,
        selectedTypeId: null,
        newTypeName: '',
        newTypePalette: {textColor: 'var(--color-secondary-150)', bgColor: 'var(--color-brand-100)' },
        showColorPicker: false,
        pendingDeleteTypeId: null,
        confirmingDeleteRel: false,
        confirmingDeleteType: false,
        charFromName: null,
        charToName: null,
        usedTypeIds: [],
        types: @js($relationshipTypes),
        palette: @js(config('system_colors')),
        isDuplicateTypeName() {
            const name = this.newTypeName.trim().toLowerCase();
            if (name === '') return false;
            return this.types.some(t => t.name.trim().toLowerCase() === name);
        },
        isTypeDisabled(typeId) {
            return this.usedTypeIds.includes(typeId) && typeId !== this.editingRelationSavedTypeId;
        },
        selectType(typeId) {
            if (this.isTypeDisabled(typeId)) return;
            this.selectedTypeId = typeId;
            this.newTypeName = '';
            this.newTypePalette = {textColor: 'var(--color-secondary-150)', bgColor: 'var(--color-brand-100)' };
            this.showColorPicker = false;
        },
        async saveRelation() {
            let type = null;

            if (this.newTypeName.trim() !== '') {
                if (this.isDuplicateTypeName()) return;
                type = await this.$wire.call('createRelationshipType', this.newTypeName.trim(), this.newTypePalette.textColor, this.newTypePalette.bgColor);
                this.newTypeName = ''; 
                if (type && !this.types.find(t => t.id === type.id)) this.types.push(type);
            } else if (this.selectedTypeId) {
                type = this.types.find(t => t.id === this.selectedTypeId);
            }

            if (!type) return;

            if (this.editingRelationId) {
                await this.$wire.call('updateRelationship', this.editingRelationId, type.id);
                window.dispatchEvent(new CustomEvent('relation-saved', { detail: { relationId: this.editingRelationId, type } }));
            } else {
                window.dispatchEvent(new CustomEvent('type-selected', { detail: { type } }));
            }

            this.close();
        },
        async deleteType(typeId) {
            const shouldCloseForEdit = this.editingRelationId && this.editingRelationSavedTypeId === typeId;
            this.types = this.types.filter(t => t.id !== typeId);
            if (this.selectedTypeId === typeId) this.selectedTypeId = null;
            this.pendingDeleteTypeId = null;
            this.confirmingDeleteType = false;
            await this.$wire.call('deleteRelationshipType', typeId);
            window.dispatchEvent(new CustomEvent('relation-type-deleted', { detail: { typeId } }));
            if (shouldCloseForEdit) this.close();
        },
        async deleteRelation() {
            if (!this.editingRelationId) return;
            const relationId = this.editingRelationId;
            await this.$wire.call('deleteRelationship', relationId);
            window.dispatchEvent(new CustomEvent('relation-deleted', { detail: { relationId } }));
            this.close();
        },
        close() {
            this.showPopup = false;
            this.editingRelationId = null;
            this.editingRelationSavedTypeId = null;
            this.selectedTypeId = null;
            this.newTypeName = '';
            this.newTypePalette = {textColor: 'var(--color-secondary-150)', bgColor: 'var(--color-brand-100)' };
            this.showColorPicker = false;
            this.pendingDeleteTypeId = null;
            this.confirmingDeleteRel = false;
            this.confirmingDeleteType = false;
            this.charFromName = null;
            this.charToName = null;
            this.usedTypeIds = [];
            window.dispatchEvent(new CustomEvent('relation-popup-closed'));
        },
    }"
    @open-relation-type-popup.window="
        editingRelationId = null;
        editingRelationSavedTypeId = null;
        selectedTypeId = null;
        newTypeName = '';
        newTypePalette = {textColor: 'var(--color-secondary-150)', bgColor: 'var(--color-brand-100)' };
        showColorPicker = false;
        confirmingDeleteRel = false;
        confirmingDeleteType = false;
        charFromName = $event.detail.charFromName ?? null;
        charToName = $event.detail.charToName ?? null;
        usedTypeIds = $event.detail.usedTypeIds ?? [];
        showPopup = true;
    "
    @open-edit-relation-popup.window="
        editingRelationId = $event.detail.relationId;
        editingRelationSavedTypeId = $event.detail.typeId ?? null;
        selectedTypeId = $event.detail.typeId ?? null;
        charFromName = $event.detail.charFromName ?? null;
        charToName = $event.detail.charToName ?? null;
        usedTypeIds = $event.detail.usedTypeIds ?? [];
        newTypeName = '';
        newTypePalette = {textColor: 'var(--color-secondary-150)', bgColor: 'var(--color-brand-100)' };
        showColorPicker = false;
        confirmingDeleteRel = false;
        confirmingDeleteType = false;
        showPopup = true;
    "
>
    <div
        x-show="showPopup"
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center bg-text-80/50 backdrop-blur-[1.5px]"
    >
        <div
            @click.away="close()"
            class="relative bg-bg-main border border-brand-100 rounded-xl shadow-xl p-6 w-full max-w-md flex flex-col gap-4"
        >

            <button
                @click="close()"
                class="absolute top-3 right-3 z-10 w-7 h-7 flex items-center justify-center rounded-full hover:bg-brand-100 transition-colors"
            >
                <svg class="w-3.5 h-3.5 text-text-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            {{-- Normal view --}}
            <div x-show="!confirmingDeleteRel && !confirmingDeleteType" class="flex flex-col gap-6">

                <div class="flex flex-col gap-1 text-center">
                    <h3 class="text-app-title-1 text-[28px] text-text-80" x-text="editingRelationId ? 'Edit Relationship' : 'Choose Relationship Type'"></h3>
                    <div x-show="charFromName && charToName" style="display:none;" 
                        class="flex flex-row items-center justify-center gap-3">
                        
                        <!-- Nama Karakter Pertama -->
                        <p class="text-app-desc-feature text-text-80 bg-brand-100 rounded-full px-4 py-1.5 border border-brand-150"
                        x-text="charFromName">
                        </p>

                        <!-- Ikon Penghubung -->
                        <div class="text-secondary-100 flex items-center justify-center">
                            <x-icons.relationship class="w-5 h-5"/>
                        </div>

                        <!-- Nama Karakter Kedua -->
                        <p class="text-app-desc-feature text-text-80 bg-brand-100 rounded-full px-4 py-1.5 border border-brand-150"
                        x-text="charToName">
                        </p>
                        
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <div class="flex flex-wrap gap-2 max-h-40 overflow-y-auto custom-scrollbar">
                        <template x-for="rt in types" :key="rt.id">
                            <div
                                class="flex items-center gap-1 rounded-full pl-3 pr-1.5 py-1.5 border-2 transition-all"
                                :class="isTypeDisabled(rt.id) ? 'opacity-40' : ''"
                                :style="`background-color: ${rt.bgColor}; border-color: ${selectedTypeId === rt.id ? rt.textColor : 'transparent'};`"
                            >
                                <button
                                    @click="selectType(rt.id)"
                                    :disabled="isTypeDisabled(rt.id)"
                                    class="text-app-body-medium"
                                    :class="isTypeDisabled(rt.id) ? 'cursor-not-allowed' : ''"
                                    :style="`color: ${rt.textColor}`"
                                    x-text="rt.name"
                                ></button>
                                <button
                                    @click.stop="pendingDeleteTypeId = rt.id; confirmingDeleteType = true"
                                    class="w-4 h-4 rounded-full flex items-center justify-center hover:bg-black/10 transition-colors"
                                    :style="`color: ${rt.textColor}`"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="relative flex-1 min-w-0">
                            <input
                                type="text"
                                x-model="newTypeName"
                                @input="selectedTypeId = null"
                                @keydown.enter="if (!((!selectedTypeId && newTypeName.trim() === '') || isDuplicateTypeName())) saveRelation()"
                                maxlength="20"
                                placeholder="New type..."
                                :class="isDuplicateTypeName() ? 'border-danger-100' : 'border-brand-200'"
                                class="w-full px-3 py-2 pr-10 bg-bg-main border rounded-md outline-none text-app-body-small text-text-80 focus:border-secondary-150 focus:border-2 transition-colors"
                            >
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-app-desc-feature text-subtext-90 pointer-events-none">
                                <span x-text="newTypeName.length"></span>/20
                            </span>
                        </div>
                        <div class="relative shrink-0">
                            <button
                                type="button"
                                @click="showColorPicker = !showColorPicker"
                                class="w-8 h-8 rounded-full border-2 border-secondary-100 shadow-sm transition-colors"
                                :style="`background-color: ${newTypePalette.textColor};`"
                            ></button>
                            <div
                                x-show="showColorPicker"
                                @click.away="showColorPicker = false"
                                style="display: none;"
                                class="absolute bottom-full right-0 mb-2 z-10 grid grid-cols-3 gap-2 bg-bg-main border border-brand-150 rounded-lg p-2 w-[calc(3*1.75rem+2*0.5rem+2*0.5rem)]"
                            >
                                <template x-for="(p, pIdx) in palette" :key="pIdx">
                                    <button
                                        type="button"
                                        @click="newTypePalette = p; selectedTypeId = null; showColorPicker = false"
                                        class="w-7 h-7 rounded-full border-2 transition-all"
                                        :style="`background-color: ${p.textColor}; border-color: ${newTypePalette.bgColor === p.bgColor ? 'secondary-100' : 'transparent'};`"
                                    ></button>
                                </template>
                            </div>
                        </div>
                    </div>
                    <p x-show="isDuplicateTypeName()" style="display: none;" class="text-app-desc-feature text-danger-100 -mt-1">Type already exists.</p>

                    <div class="flex gap-2 mt-2">
                        <button 
                               x-show="editingRelationId" 
                               style="display:none;" 
                               @click="confirmingDeleteRel = true" 
                               class="cursor-pointer flex-1 py-3 rounded-lg border border-danger-100 text-app-feature text-danger-100 hover:bg-danger-100/10 transition-colors"
                            >Delete
                        </button>

                        <button
                            @click="saveRelation()"
                            :disabled="(!selectedTypeId && newTypeName.trim() === '') || isDuplicateTypeName()"
                            :class="((!selectedTypeId && newTypeName.trim() === '') || isDuplicateTypeName()) ? 'opacity-40 cursor-not-allowed' : ''"
                            class="cursor-pointer flex-1 py-3 rounded-lg bg-secondary-100 text-app-feature text-bg-main hover:bg-secondary-150 transition-colors"
                        >Save</button>
                    </div>
                </div>
            </div>

            {{-- Confirm delete relationship (Custom with confirm-dialog style) --}}
            <div x-show="confirmingDeleteRel" style="display:none;" class="flex flex-col text-center gap-8 py-2">
                <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-danger-100/10">
                    <div class="flex items-center justify-center text-danger-100">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                    </div>
                </div>

                <div class="flex flex-col w-full gap-5">
                    <h3 class="text-app-heading-1 text-text-80">Delete Relationship?</h3>
                    <p class="text-app-subfeature text-text-80 px-3">This relationship will be permanently removed.</p>
                </div>

                <div class="flex gap-4 w-full justify-center">
                    <button @click="confirmingDeleteRel = false" class="flex-1 py-2 px-4 rounded-lg border border-card-border text-text-70 text-web-body-small font-semibold hover:bg-card-hover transition-colors">Cancel</button>
                    <button @click="deleteRelation()" class="flex-1 py-3 px-4 rounded-lg text-subtext-60 transition-colors text-web-body-small font-semibold bg-danger-100 hover:bg-red-600">Confirm Delete</button>
                </div>
            </div>

            {{-- Confirm delete relationship type (Custom with confirm-dialog style) --}}
            <div x-show="confirmingDeleteType" style="display:none;" class="flex flex-col text-center gap-8 py-2">
                <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-danger-100/10">
                    <div class="flex items-center justify-center text-danger-100">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                    </div>
                </div>

                <div class="flex flex-col w-full gap-5">
                    <h3 class="text-app-heading-1 text-text-80">Delete Relationship Type?</h3>
                    <p class="text-app-subfeature text-text-80 px-3">"<span x-text="types.find(t => t.id === pendingDeleteTypeId)?.name"></span>" and every relationship using it will be permanently removed.</p>
                </div>

                <div class="flex gap-4 w-full justify-center">
                    <button @click="confirmingDeleteType = false; pendingDeleteTypeId = null" class="flex-1 py-2 px-4 rounded-lg border border-card-border text-text-70 text-web-body-small font-semibold hover:bg-card-hover transition-colors">Cancel</button>
                    <button @click="deleteType(pendingDeleteTypeId)" class="flex-1 py-3 px-4 rounded-lg text-subtext-60 transition-colors text-web-body-small font-semibold bg-danger-100 hover:bg-red-600">Confirm Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>
