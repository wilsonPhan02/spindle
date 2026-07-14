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
        $name = mb_substr(trim($name), 0, 30);
        if ($name === '') {
            return null;
        }

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
        $name = mb_substr(trim($name), 0, 30);
        if ($name === '') {
            return;
        }

        $this->project->characterDetailGroups()->where('character_detail_group_id', $groupId)->update(['name' => $name]);
        $this->dispatch('detail-groups-changed');
    }

    #[Renderless]
    public function createDetailField($groupId, $name) {
        $name = mb_substr(trim($name), 0, 30);
        if ($name === '') {
            return null;
        }

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
        $name = mb_substr(trim($name), 0, 30);
        if ($name === '') {
            return;
        }

        CharacterDetailField::where('character_detail_field_id', $fieldId)
            ->whereHas('group', fn ($query) => $query->where('project_id', $this->project->project_id))
            ->update(['name' => $name]);
        $this->dispatch('detail-groups-changed');
    }

    #[Renderless]
    public function updateGroupOrder($orderedGroupIds) {
        $groups = $this->project->characterDetailGroups()->whereIn('character_detail_group_id', $orderedGroupIds)->get();
        $availableOrders = $groups->pluck('order')->sort()->values()->toArray();

        DB::transaction(function () use ($orderedGroupIds, $availableOrders) {
            foreach ($orderedGroupIds as $index => $groupId) {
                CharacterDetailGroup::where('character_detail_group_id', $groupId)->update(['order' => $availableOrders[$index]]);
            }
        });

        $this->dispatch('detail-groups-changed');
    }

    #[Renderless]
    public function updateFieldOrder($groupId, $orderedFieldIds) {
        $group = $this->project->characterDetailGroups()->where('character_detail_group_id', $groupId)->first();
        if (! $group) {
            return;
        }

        $fields = $group->fields()->whereIn('character_detail_field_id', $orderedFieldIds)->get();
        $availableOrders = $fields->pluck('order')->sort()->values()->toArray();

        DB::transaction(function () use ($orderedFieldIds, $availableOrders) {
            foreach ($orderedFieldIds as $index => $fieldId) {
                CharacterDetailField::where('character_detail_field_id', $fieldId)->update(['order' => $availableOrders[$index]]);
            }
        });

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
        init() {
            new Sortable(this.$refs.groupList, {
                animation: 200,
                ghostClass: 'opacity-50',
                handle: '.drag-handle',
                draggable: '.sortable-group',
                onEnd: (evt) => {
                    if (evt.oldIndex === evt.newIndex) return;
                    const orderedIds = Array.from(this.$refs.groupList.querySelectorAll('.sortable-group')).map(el => el.getAttribute('data-id'));
                    this.detailGroups.sort((a, b) => orderedIds.indexOf(a.id) - orderedIds.indexOf(b.id));
                    this.$wire.call('updateGroupOrder', orderedIds);
                },
            });
        },
        initFieldSortable(el, groupId) {
            new Sortable(el, {
                animation: 200,
                ghostClass: 'opacity-50',
                draggable: '.sortable-field',
                onEnd: (evt) => {
                    if (evt.oldIndex === evt.newIndex) return;
                    const orderedIds = Array.from(el.querySelectorAll('.sortable-field')).map(item => item.getAttribute('data-id'));
                    const group = this.detailGroups.find(g => g.id === groupId);
                    if (group) group.fields.sort((a, b) => orderedIds.indexOf(a.id) - orderedIds.indexOf(b.id));
                    this.$wire.call('updateFieldOrder', groupId, orderedIds);
                },
            });
        },
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
        openNewFieldInput(targetGroupId) {
            for (const id in this.showNewFieldInput) {
                if (this.showNewFieldInput[id] && id != targetGroupId) {
                    this.addField(id);
                }
            }
            this.showNewFieldInput[targetGroupId] = true;
        },
        renameGroup(group) {
            const name = this.editGroupName.trim();
            if (name === '' || name === group.name) { this.editingGroupId = null; this.groupNameError = ''; return; }
            if (this.detailGroups.some(g => g.id !== group.id && g.name.trim().toLowerCase() === name.toLowerCase())) {
                this.groupNameError = '{{ __('Group name already exists.') }}';
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
                this.fieldNameError = '{{ __('Field name already exists in this group.') }}';
                return;
            }
            this.fieldNameError = '';
            this.editingFieldId = null;
            field.name = name;
            this.$wire.call('renameDetailField', field.id, name);
        },
        async addField(groupId) {
            const name = (this.newFieldName[groupId] || '').trim();
            if (name === '') {
                this.showNewFieldInput[groupId] = false;
                return;
            }
            const group = this.detailGroups.find(g => g.id === groupId);
            if (!group) return;
            if (group.fields.some(f => f.name.trim().toLowerCase() === name.toLowerCase())) {
                this.fieldNameError = '{{ __('Field name already exists in this group.') }}';
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
                this.groupNameError = '{{ __('Group name already exists.') }}';
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
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm"
    wire:ignore
>
    <div @click.away="show = false" class="bg-brand-10 rounded-2xl border-2 border-brand-150 shadow-2xl w-full max-h-[90vh] max-w-2xl p-10 flex flex-col gap-6 relative">

        <button @click="show = false" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full text-text-60 hover:bg-brand-100 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <h2 class="text-app-title-1 text-text-100">{{ __('Character Details') }}</h2>

        <div class="flex flex-col gap-6 max-h-[28rem] overflow-y-auto pr-2 custom-scrollbar" x-ref="groupList">
            <template x-for="group in detailGroups" :key="group.id">
                <div class="group/detail flex items-start gap-1 sortable-group" :data-id="group.id">
                    <div class="drag-handle shrink-0 p-1.5 opacity-0 group-hover/detail:opacity-100 text-text-60 hover:text-text-80 cursor-grab active:cursor-grabbing transition-opacity">
                        <x-icons.drag-handle class="w-1.5 h-3" />
                    </div>
                    <div class="flex-1 min-w-0 flex flex-col gap-3 text-left">
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
                                maxlength="30"
                                class="text-app-subheading-2 font-semibold text-secondary-200 bg-transparent border-b border-secondary-200 outline-none"
                            >
                        </template>
                        <span x-show="editingGroupId === group.id" x-cloak class="text-app-desc-feature text-subtext-90 shrink-0" x-text="editGroupName.length + '/30'"></span>
                        <button x-show="editingGroupId !== group.id" @click="editingGroupId = group.id; editGroupName = group.name" class="text-secondary-200 hover:text-secondary-300 hover:bg-brand-50 rounded p-1 transition-colors">
                            <x-icons.rename class="w-4 h-4 stroke-2" />
                        </button>
                        <button @click="confirmingDeleteGroupId = group.id" class="text-danger-100 hover:opacity-70 transition-opacity">
                            <x-icons.delete class="w-4 h-4" />
                        </button>
                    </div>
                    <p x-show="editingGroupId === group.id && groupNameError" style="display:none;" class="text-app-desc-feature text-danger-100 -mt-1" x-text="groupNameError"></p>

                    <div class="flex flex-wrap items-center gap-2" x-init="initFieldSortable($el, group.id)">
                        <template x-for="field in group.fields" :key="field.id">
                            <div class="flex items-center gap-1 rounded-full bg-brand-100 pl-4 pr-2 py-2 max-w-full min-w-0 sortable-field cursor-move" :data-id="field.id">
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
                                        maxlength="30"
                                        class="text-app-body-medium text-text-90 min-w-[20px] [field-sizing:content] border-b border-secondary-200 outline-none"
                                    >
                                </template>
                                <span x-show="editingFieldId === field.id" x-cloak class="text-app-desc-feature text-subtext-90 shrink-0" x-text="editFieldName.length + '/30'"></span>
                                <button @click="confirmingDeleteFieldId = field.id" class="w-4 h-4 rounded-full flex items-center justify-center text-text-60 hover:bg-black/10 hover:text-danger-100 transition-colors">
                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>

                        <button x-show="!showNewFieldInput[group.id]" @click="openNewFieldInput(group.id)" type="button" class="w-7 h-7 cursor-pointer  rounded-full bg-brand-100 flex items-center justify-center text-text-70 hover:bg-brand-150 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        </button>
                        <div x-show="showNewFieldInput[group.id]" @click.away="addField(group.id)" class="flex items-center gap-1">
                            <div class="relative shrink-0">
                                <input
                                    type="text"
                                    x-model="newFieldName[group.id]"
                                    x-effect="if (showNewFieldInput[group.id]) $nextTick(() => $el.focus())"
                                    @keydown.enter="addField(group.id)"
                                    @keydown.escape="addField(group.id)"
                                    @input="fieldNameError = ''"
                                    maxlength="30"
                                    placeholder="{{ __('New field...') }}"
                                    class="pl-3 pr-11 py-1.5 rounded-full bg-brand-100 border border-secondary-200 outline-none text-app-body-medium text-text-90 w-36"
                                >
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-app-desc-feature text-subtext-90 pointer-events-none" x-text="(newFieldName[group.id] || '').length + '/30'"></span>
                            </div>
                            <button @click="showNewFieldInput[group.id] = false; newFieldName[group.id] = ''; fieldNameError = ''" type="button" class="w-5 h-5 cursor-pointer rounded-full flex items-center justify-center text-text-60 hover:bg-black/10 hover:text-danger-100 transition-colors">
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    <p x-show="(showNewFieldInput[group.id] || group.fields.some(f => f.id === editingFieldId)) && fieldNameError" style="display:none;" class="text-app-desc-feature text-danger-100" x-text="fieldNameError"></p>
                    </div>
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
                        maxlength="30"
                        placeholder="{{ __('New group name...') }}"
                        class="text-app-subheading-2 font-semibold text-secondary-200 bg-transparent border-b border-secondary-200 outline-none min-w-0 flex-1"
                    >
                    <button @click="showNewGroupInput = false; newGroupName = ''; groupNameError = ''" type="button" class="w-6 h-6 rounded-full flex items-center justify-center text-text-60 hover:bg-black/10 hover:text-danger-100 transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <span class="text-app-desc-feature text-subtext-90 -mt-2" x-text="newGroupName.length + '/30'"></span>
                <p x-show="groupNameError" style="display:none;" class="text-app-desc-feature text-danger-100 -mt-2" x-text="groupNameError"></p>
            </div>

        </div>

        <div class="flex justify-end">
            <button @click="showNewGroupInput = true" type="button" class="cursor-pointer px-8 py-4 rounded-lg bg-secondary-100 text-bg-main text-app-feature hover:bg-secondary-150 transition-colors">
                {{ __('+ Add Group') }}
            </button>
        </div>

        {{-- Konfirmasi hapus Group / Field, ukuran & gaya sama dengan popup konfirmasi di edit relation --}}
        <div
            x-show="confirmingDeleteGroupId || confirmingDeleteFieldId"
            style="display: none;"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black/70 backdrop-blur-sm"
        >
            <div @click.away="confirmingDeleteGroupId = null; confirmingDeleteFieldId = null" class="flex flex-col bg-card-bg border border-card-border rounded-2xl shadow-2xl w-full max-w-md px-12 py-8 text-center gap-8">
                <template x-if="confirmingDeleteGroupId">
                    <div class="flex flex-col items-center w-full gap-8">
                        <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-danger-100/10">
                            <div class="flex items-center justify-center text-danger-100">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                            </div>
                        </div>

                        <div class="flex flex-col w-full gap-5">
                            <h3 class="text-app-heading-1 text-text-80">{{ __('Delete Detail Group?') }}</h3>
                            <p class="text-app-subfeature text-text-80 px-3">{{ __('This group and every field & value inside it will be permanently removed for all characters.') }}</p>
                        </div>

                        <div class="flex gap-4 w-full justify-center">
                            <button @click="confirmingDeleteGroupId = null" class="flex-1 py-2 px-4 rounded-lg border border-card-border text-text-70 text-web-body-small font-semibold hover:bg-card-hover transition-colors">{{ __('Cancel') }}</button>
                            <button @click="deleteGroup(confirmingDeleteGroupId)" class="flex-1 py-3 px-4 rounded-lg text-bg-main transition-colors text-web-body-small font-semibold bg-danger-100 hover:bg-danger-100/90">{{ __('Delete') }}</button>
                        </div>
                    </div>
                </template>

                <template x-if="confirmingDeleteFieldId">
                    <div class="flex flex-col items-center w-full gap-8">
                        <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-danger-100/10">
                            <div class="flex items-center justify-center text-danger-100">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                            </div>
                        </div>

                        <div class="flex flex-col w-full gap-5">
                            <h3 class="text-app-heading-1 text-text-80">{{ __('Delete Field?') }}</h3>
                            <p class="text-app-subfeature text-text-80 px-3">{{ __('This field and its value for every character will be permanently removed.') }}</p>
                        </div>

                        <div class="flex gap-4 w-full justify-center">
                            <button @click="confirmingDeleteFieldId = null" class="flex-1 py-2 px-4 rounded-lg border border-card-border text-text-70 text-web-body-small font-semibold hover:bg-card-hover transition-colors">{{ __('Cancel') }}</button>
                            <button @click="deleteField(confirmingDeleteFieldId)" class="flex-1 py-3 px-4 rounded-lg text-bg-main transition-colors text-web-body-small font-semibold bg-danger-100 hover:bg-danger-100/90">{{ __('Delete') }}</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
