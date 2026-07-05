<?php

use Livewire\Volt\Component;
use App\Models\Project;
use App\Models\CharacterDetailGroup;
use App\Models\CharacterDetailField;
use Livewire\Attributes\Renderless;

new class extends Component {
    public Project $project;

    public array $detailGroups = [];

    public function mount(Project $project) {
        $this->project = $project;
        $this->project->seedDefaultCharacterDetailGroups();
        $this->loadDetailGroups();
    }

    private function loadDetailGroups() {
        $this->detailGroups = $this->project->characterDetailGroups()
            ->with('fields')
            ->orderBy('order')
            ->get()
            ->map(fn (CharacterDetailGroup $group) => $this->mapDetailGroup($group))
            ->all();
    }

    private function mapDetailGroup(CharacterDetailGroup $group): array {
        return [
            'id' => $group->character_detail_group_id,
            'name' => $group->name,
            'fields' => $group->fields->sortBy('order')->values()->map(fn (CharacterDetailField $field) => [
                'id' => $field->character_detail_field_id,
                'name' => $field->name,
            ])->all(),
        ];
    }

    #[Renderless]
    public function createDetailGroup($name) {
        $maxOrder = $this->project->characterDetailGroups()->max('order') ?? -1;
        $group = $this->project->characterDetailGroups()->create([
            'name' => $name,
            'order' => $maxOrder + 1,
        ]);

        $mapped = $this->mapDetailGroup($group->load('fields'));
        $this->detailGroups[] = $mapped;
        $this->dispatch('detail-groups-changed');

        return $mapped;
    }

    #[Renderless]
    public function deleteDetailGroup($groupId) {
        // Field & Value di dalam group ini ikut terhapus (cascadeOnDelete di migration)
        $this->project->characterDetailGroups()->where('character_detail_group_id', $groupId)->delete();
        $this->detailGroups = array_values(array_filter($this->detailGroups, fn ($group) => $group['id'] !== $groupId));
        $this->dispatch('detail-groups-changed');
    }

    #[Renderless]
    public function renameDetailGroup($groupId, $name) {
        $this->project->characterDetailGroups()->where('character_detail_group_id', $groupId)->update(['name' => $name]);
        $this->dispatch('detail-groups-changed');
    }

    #[Renderless]
    public function createDetailField($groupId, $name) {
        $group = $this->project->characterDetailGroups()->where('character_detail_group_id', $groupId)->first();
        if (! $group) {
            return null;
        }

        $maxOrder = $group->fields()->max('order') ?? -1;
        $field = $group->fields()->create([
            'name' => $name,
            'order' => $maxOrder + 1,
        ]);

        $this->dispatch('detail-groups-changed');

        return [
            'id' => $field->character_detail_field_id,
            'name' => $field->name,
        ];
    }

    #[Renderless]
    public function deleteDetailField($fieldId) {
        // Value milik field ini ikut terhapus (cascadeOnDelete di migration)
        CharacterDetailField::where('character_detail_field_id', $fieldId)
            ->whereHas('group', fn ($query) => $query->where('project_id', $this->project->project_id))
            ->delete();
        $this->dispatch('detail-groups-changed');
    }

    #[Renderless]
    public function renameDetailField($fieldId, $name) {
        CharacterDetailField::where('character_detail_field_id', $fieldId)
            ->whereHas('group', fn ($query) => $query->where('project_id', $this->project->project_id))
            ->update(['name' => $name]);
        $this->dispatch('detail-groups-changed');
    }
}; ?>

{{-- MODAL: Character Details (kelola Group & Field template, bukan per-karakter) — komponen reusable,
     dipanggil dari halaman manapun lewat <livewire:projects.character-details-popup :project="$project" />,
     dibuka lewat window event 'open-edit-characters' --}}
<div
    x-data="{
        show: false,
        detailGroups: @js($detailGroups),
        editingGroupId: null,
        editGroupName: '',
        editingFieldId: null,
        editFieldName: '',
        confirmingDeleteGroupId: null,
        confirmingDeleteFieldId: null,
        showNewFieldInput: {},
        newFieldName: {},
        showNewGroupInput: false,
        newGroupName: '',
        groupNameError: '',
        fieldNameError: '',
        resetDetailUi() {
            this.editingGroupId = null;
            this.editGroupName = '';
            this.editingFieldId = null;
            this.editFieldName = '';
            this.confirmingDeleteGroupId = null;
            this.confirmingDeleteFieldId = null;
            this.showNewFieldInput = {};
            this.newFieldName = {};
            this.showNewGroupInput = false;
            this.newGroupName = '';
            this.groupNameError = '';
            this.fieldNameError = '';
        },
        renameGroup(group) {
            const name = this.editGroupName.trim();
            if (name === '' || name === group.name) { this.editingGroupId = null; this.groupNameError = ''; return; }
            if (this.detailGroups.some(g => g.id !== group.id && g.name.trim().toLowerCase() === name.toLowerCase())) {
                this.groupNameError = 'Group name already exists.';
                return;
            }
            this.groupNameError = '';
            this.editingGroupId = null;
            group.name = name;
            this.$wire.call('renameDetailGroup', group.id, name);
        },
        deleteGroup(groupId) {
            this.detailGroups = this.detailGroups.filter(g => g.id !== groupId);
            this.confirmingDeleteGroupId = null;
            this.$wire.call('deleteDetailGroup', groupId);
        },
        renameField(field) {
            const name = this.editFieldName.trim();
            if (name === '' || name === field.name) { this.editingFieldId = null; this.fieldNameError = ''; return; }
            const group = this.detailGroups.find(g => g.fields.some(f => f.id === field.id));
            if (group && group.fields.some(f => f.id !== field.id && f.name.trim().toLowerCase() === name.toLowerCase())) {
                this.fieldNameError = 'Field name already exists in this group.';
                return;
            }
            this.fieldNameError = '';
            this.editingFieldId = null;
            field.name = name;
            this.$wire.call('renameDetailField', field.id, name);
        },
        async addField(groupId) {
            const name = (this.newFieldName[groupId] || '').trim();
            if (name === '') return;
            const group = this.detailGroups.find(g => g.id === groupId);
            if (!group) return;
            if (group.fields.some(f => f.name.trim().toLowerCase() === name.toLowerCase())) {
                this.fieldNameError = 'Field name already exists in this group.';
                return;
            }
            this.fieldNameError = '';
            this.showNewFieldInput[groupId] = false;
            this.newFieldName[groupId] = '';

            // Optimistic: tampilkan chip-nya seketika (ID sementara), baru di-sync
            // ke ID asli dari server setelah $wire.call selesai di belakang layar.
            const tempId = 'temp-' + Date.now() + '-' + Math.random().toString(36).slice(2);
            group.fields.push({ id: tempId, name });

            const field = await this.$wire.call('createDetailField', groupId, name);
            const idx = group.fields.findIndex(f => f.id === tempId);
            if (idx !== -1 && field) group.fields[idx] = field;
        },
        deleteField(fieldId) {
            this.detailGroups.forEach(g => { g.fields = g.fields.filter(f => f.id !== fieldId); });
            this.confirmingDeleteFieldId = null;
            this.$wire.call('deleteDetailField', fieldId);
        },
        async addGroup() {
            const name = this.newGroupName.trim();
            if (name === '') return;
            if (this.detailGroups.some(g => g.name.trim().toLowerCase() === name.toLowerCase())) {
                this.groupNameError = 'Group name already exists.';
                return;
            }
            this.groupNameError = '';
            this.showNewGroupInput = false;
            this.newGroupName = '';

            // Optimistic: tampilkan group-nya seketika (ID sementara), baru di-sync
            // ke ID asli dari server setelah $wire.call selesai di belakang layar.
            const tempId = 'temp-' + Date.now() + '-' + Math.random().toString(36).slice(2);
            this.detailGroups.push({ id: tempId, name, fields: [] });

            const group = await this.$wire.call('createDetailGroup', name);
            const idx = this.detailGroups.findIndex(g => g.id === tempId);
            if (idx !== -1 && group) this.detailGroups[idx] = group;
        },
    }"
    @open-edit-characters.window="show = true; resetDetailUi()"
    x-show="show"
    style="display: none;"
    class="fixed inset-0 z-50 flex items-center justify-center bg-text-80/75 backdrop-blur-[1.5px]"
    wire:ignore
>
    <div @click.away="show = false" class="bg-brand-10 rounded-2xl border-2 border-brand-150 shadow-2xl w-full max-h-[90vh] max-w-2xl p-10 flex flex-col gap-6 relative">

        <button @click="show = false" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full text-text-60 hover:bg-brand-100 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <h2 class="text-app-title-1 text-text-100">Character Details</h2>

        <div class="flex flex-col gap-6 max-h-[28rem] overflow-y-auto pr-2 
                    [&::-webkit-scrollbar]:w-1.5 
                    [&::-webkit-scrollbar-track]:bg-transparent 
                    [&::-webkit-scrollbar-thumb]:bg-[var(--color-brand-50)] 
                    [&::-webkit-scrollbar-thumb]:rounded-full 
                    [&::-webkit-scrollbar-thumb:hover]:bg-[var(--color-brand-100)] 
                    [&::-webkit-scrollbar-button]:w-0 
                    [&::-webkit-scrollbar-button]:h-0
                    [&::-webkit-scrollbar-button]:!hidden
                    [scrollbar-width:thin] 
                    [scrollbar-color:#D5C6A9_transparent]">
            <template x-for="group in detailGroups" :key="group.id">
                <div class="flex flex-col gap-3 text-left">
                    <div class="flex items-center gap-2 min-w-0">
                        <template x-if="editingGroupId !== group.id">
                            <h3 
                                class="text-app-subheading-2 text-secondary-200 truncate min-w-0 shrink cursor-pointer" 
                                @dblclick="editingGroupId = group.id; editGroupName = group.name" 
                                x-text="group.name"
                            ></h3>
                        </template>
                        
                        <template x-if="editingGroupId === group.id">
                            <input
                                type="text"
                                x-model="editGroupName"
                                x-init="$nextTick(() => $el.focus())"
                                @keydown.enter="renameGroup(group)"
                                @keydown.escape="editingGroupId = null; groupNameError = ''"
                                @blur="renameGroup(group)"
                                @input="groupNameError = ''"
                                class="text-app-subheading-2 font-semibold text-secondary-200 bg-transparent border-b border-secondary-200 outline-none"
                            >
                        </template>
                        <button x-show="editingGroupId !== group.id" @click="editingGroupId = group.id; editGroupName = group.name" class="text-secondary-200 hover:text-secondary-300 hover:bg-brand-50 rounded p-1 transition-colors">
                            <x-icons.rename class="w-4 h-4 stroke-2" />
                        </button>
                        <button @click="confirmingDeleteGroupId = group.id" class="text-danger-100 hover:opacity-70 transition-opacity">
                            <x-icons.delete class="w-4 h-4" />
                        </button>
                    </div>
                    <p x-show="editingGroupId === group.id && groupNameError" style="display:none;" class="text-app-desc-feature text-danger-100 -mt-1" x-text="groupNameError"></p>

                    <div class="flex flex-wrap items-center gap-2">
                        <template x-for="field in group.fields" :key="field.id">
                            <div class="flex items-center gap-1 rounded-full bg-brand-100 pl-4 pr-2 py-2 max-w-full min-w-0">
                                <template x-if="editingFieldId !== field.id">
                                    <span @click="editingFieldId = field.id; editFieldName = field.name" class="text-app-body-medium text-text-80 cursor-text truncate min-w-0 flex-1" x-text="field.name"></span>
                                </template>
                                <template x-if="editingFieldId === field.id">
                                    <input
                                        type="text"
                                        x-model="editFieldName"
                                        x-init="$nextTick(() => $el.focus())"
                                        @keydown.enter="renameField(field)"
                                        @keydown.escape="editingFieldId = null; fieldNameError = ''"
                                        @blur="renameField(field)"
                                        @input="fieldNameError = ''"
                                        @click.stop
                                        class="text-app-body-medium text-text-90 min-w-[20px] [field-sizing:content] border-b border-secondary-200 outline-none"
                                    >
                                </template>
                                <button @click="confirmingDeleteFieldId = field.id" class="w-4 h-4 rounded-full flex items-center justify-center text-text-60 hover:bg-black/10 hover:text-danger-100 transition-colors">
                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>

                        <button x-show="!showNewFieldInput[group.id]" @click="showNewFieldInput[group.id] = true" type="button" class="w-7 h-7 cursor-pointer  rounded-full bg-brand-100 flex items-center justify-center text-text-70 hover:bg-brand-150 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        </button>
                        <div x-show="showNewFieldInput[group.id]" class="flex items-center gap-1">
                            <input
                                type="text"
                                x-model="newFieldName[group.id]"
                                x-init="$nextTick(() => $el.focus())"
                                @keydown.enter="addField(group.id)"
                                @keydown.escape="showNewFieldInput[group.id] = false; newFieldName[group.id] = ''; fieldNameError = ''"
                                @input="fieldNameError = ''"
                                placeholder="New field..."
                                class="px-3 py-1.5 rounded-full bg-brand-100 border border-secondary-200 outline-none text-app-body-medium text-text-90 w-28"
                            >
                            <button @click="showNewFieldInput[group.id] = false; newFieldName[group.id] = ''; fieldNameError = ''" type="button" class="w-5 h-5 cursor-pointer rounded-full flex items-center justify-center text-text-60 hover:bg-black/10 hover:text-danger-100 transition-colors">
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    <p x-show="(showNewFieldInput[group.id] || group.fields.some(f => f.id === editingFieldId)) && fieldNameError" style="display:none;" class="text-app-desc-feature text-danger-100" x-text="fieldNameError"></p>
                </div>
            </template>

            <div x-show="showNewGroupInput" style="display: none;" class="flex flex-col gap-3 text-left">
                <div class="flex items-center gap-1 min-w-0">
                    <input
                        type="text"
                        x-model="newGroupName"
                        x-effect="if (showNewGroupInput) $nextTick(() => $el.focus())"
                        @keydown.enter="addGroup()"
                        @keydown.escape="showNewGroupInput = false; newGroupName = ''; groupNameError = ''"
                        @input="groupNameError = ''"
                        placeholder="New group name..."
                        class="text-app-subheading-2 font-semibold text-secondary-200 bg-transparent border-b border-secondary-200 outline-none min-w-0 flex-1"
                    >
                    <button @click="showNewGroupInput = false; newGroupName = ''; groupNameError = ''" type="button" class="w-6 h-6 rounded-full flex items-center justify-center text-text-60 hover:bg-black/10 hover:text-danger-100 transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <p x-show="groupNameError" style="display:none;" class="text-app-desc-feature text-danger-100 -mt-2" x-text="groupNameError"></p>
            </div>

        </div>

        <div class="flex justify-end">
            <button @click="showNewGroupInput = true" type="button" class="cursor-pointer px-8 py-4 rounded-lg bg-secondary-100 text-bg-main text-app-feature hover:bg-secondary-150 transition-colors">
                + Add Group
            </button>
        </div>

        {{-- Konfirmasi hapus Group / Field, ukuran & gaya sama dengan popup konfirmasi di edit relation --}}
        <div
            x-show="confirmingDeleteGroupId || confirmingDeleteFieldId"
            style="display: none;"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-text-80/50 backdrop-blur-[1.5px]"
        >
            <div @click.away="confirmingDeleteGroupId = null; confirmingDeleteFieldId = null" class="bg-bg-main border border-brand-100 rounded-xl shadow-xl p-6 w-full max-w-sm flex flex-col gap-4">
                <template x-if="confirmingDeleteGroupId">
                    <div class="flex flex-col gap-4">
                        <h3 class="text-app-heading-2 text-text-80 text-center">Delete Detail Group?</h3>
                        <p class="text-app-desc-feature text-text-70 text-center">This group and every field & value inside it will be permanently removed for all characters.</p>
                        <div class="flex gap-3 mt-2">
                            <button @click="confirmingDeleteGroupId = null" class="flex-1 py-2.5 rounded-lg border border-brand-200 text-app-feature text-text-80 hover:bg-brand-100 transition-colors">Cancel</button>
                            <button @click="deleteGroup(confirmingDeleteGroupId)" class="flex-1 py-2.5 rounded-lg bg-danger-100 text-app-feature text-bg-main hover:bg-danger-100/90 transition-colors">Delete</button>
                        </div>
                    </div>
                </template>

                <template x-if="confirmingDeleteFieldId">
                    <div class="flex flex-col gap-4">
                        <h3 class="text-app-heading-2 text-text-80 text-center">Delete Field?</h3>
                        <p class="text-app-desc-feature text-text-70 text-center">This field and its value for every character will be permanently removed.</p>
                        <div class="flex gap-3 mt-2">
                            <button @click="confirmingDeleteFieldId = null" class="flex-1 py-2.5 rounded-lg border border-brand-200 text-app-feature text-text-80 hover:bg-brand-100 transition-colors">Cancel</button>
                            <button @click="deleteField(confirmingDeleteFieldId)" class="flex-1 py-2.5 rounded-lg bg-danger-100 text-app-feature text-bg-main hover:bg-danger-100/90 transition-colors">Delete</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
