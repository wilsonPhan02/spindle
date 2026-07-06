<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\Project;
use App\Models\Character;
use App\Models\Relationship;
use App\Models\Hashtag;
use App\Models\CharacterDetailGroup;
use App\Models\CharacterDetailField;
use App\Models\CharacterDetailValue;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public Project $project;
    public Character $character;

    public string $fullName = '';
    public string $nickName = '';
    public ?string $bio = '';
    public string $nickNameError = '';

    public $newImage = null;
    public array $tags = [];

    public array $detailGroups = [];
    public array $detailValues = [];

    public array $relationships = [];
    public array $relationshipTypes = [];
    public array $otherCharacters = [];

    public bool $showAddRelation = false;
    public ?string $newRelationTargetId = null;
    public ?string $newRelationTypeId = null;

    public function mount(Project $project, Character $character) {
        if ($character->project_id !== $project->project_id) {
            abort(404);
        }

        $this->project = $project;
        $this->character = $character;
        $this->fullName = $character->full_name;
        $this->nickName = $character->nick_name;
        $this->bio = $character->bio;
        $this->loadDetailGroups();
        $this->loadDetailValues();
        $this->loadTags();
        $this->loadRelationships();
        $this->loadRelationshipTypes();
        $this->loadOtherCharacters();
    }

    private function loadTags() {
        $this->tags = $this->character->hashtags->map(fn ($tag) => [
            'id' => $tag->hashtag_id,
            'name' => $tag->name,
        ])->values()->all();
    }

    private function loadDetailGroups() {
        $this->detailGroups = $this->project->characterDetailGroups()
            ->with('fields')
            ->orderBy('order')
            ->get()
            ->map(fn (CharacterDetailGroup $group) => [
                'id' => $group->character_detail_group_id,
                'name' => $group->name,
                'fields' => $group->fields->sortBy('order')->values()->map(fn (CharacterDetailField $field) => [
                    'id' => $field->character_detail_field_id,
                    'name' => $field->name,
                ])->all(),
            ])
            ->all();
    }

    private function loadDetailValues() {
        $existing = CharacterDetailValue::where('character_id', $this->character->character_id)
            ->pluck('value', 'character_detail_field_id')
            ->all();

        $this->detailValues = [];
        foreach ($this->detailGroups as $group) {
            foreach ($group['fields'] as $field) {
                $this->detailValues[$field['id']] = $existing[$field['id']] ?? null;
            }
        }
    }

    // Dipanggil saat komponen <livewire:projects.character-details-popup> mengubah Group/Field-nya,
    // supaya halaman ini ikut menampilkan struktur terbaru tanpa perlu refresh manual.
    #[On('detail-groups-changed')]
    public function refreshDetailGroups() {
        $this->loadDetailGroups();
        $this->loadDetailValues();
    }

    private function loadRelationships() {
        $this->relationships = Relationship::with(['relationshipType', 'from', 'to'])
            ->where('from_id', $this->character->character_id)
            ->orWhere('to_id', $this->character->character_id)
            ->get()
            ->map(fn (Relationship $relationship) => $this->mapRelationship($relationship))
            ->all();
    }

    private function mapRelationship(Relationship $relationship): array {
        $other = $relationship->from_id === $this->character->character_id ? $relationship->to : $relationship->from;

        return [
            'id'        => $relationship->relationship_id,
            'otherId'   => $other?->character_id,
            'otherName' => $other?->nick_name ?? 'Unknown',
            'typeId'    => $relationship->relationship_type_id,
            'typeName'  => $relationship->relationshipType->name,
            'textColor' => $relationship->relationshipType->text_color,
            'bgColor'   => $relationship->relationshipType->bg_color,
        ];
    }

    private function loadRelationshipTypes() {
        $this->relationshipTypes = $this->project->relationshipTypes()->get()->map(fn ($type) => [
            'id'        => $type->relationship_type_id,
            'name'      => $type->name,
            'textColor' => $type->text_color,
            'bgColor'   => $type->bg_color,
        ])->all();
    }

    private function loadOtherCharacters() {
        $this->otherCharacters = $this->project->characters()
            ->where('character_id', '!=', $this->character->character_id)
            ->get()
            ->map(fn (Character $other) => [
                'id' => $other->character_id,
                'name' => $other->nick_name,
            ])
            ->all();
    }

    public function addRelationship() {
        if (! $this->newRelationTargetId || ! $this->newRelationTypeId) {
            return;
        }

        $duplicate = Relationship::where('relationship_type_id', $this->newRelationTypeId)
            ->where(function ($query) {
                $query->where(fn ($q) => $q->where('from_id', $this->character->character_id)->where('to_id', $this->newRelationTargetId))
                    ->orWhere(fn ($q) => $q->where('from_id', $this->newRelationTargetId)->where('to_id', $this->character->character_id));
            })
            ->exists();

        if ($duplicate) {
            return;
        }

        $relationship = Relationship::create([
            'from_id' => $this->character->character_id,
            'to_id' => $this->newRelationTargetId,
            'relationship_type_id' => $this->newRelationTypeId,
        ]);
        $relationship->load(['relationshipType', 'from', 'to']);

        $this->relationships[] = $this->mapRelationship($relationship);
        $this->newRelationTargetId = null;
        $this->newRelationTypeId = null;
        $this->showAddRelation = false;
    }

    public function deleteRelationship($relationshipId) {
        Relationship::where('relationship_id', $relationshipId)
            ->where(function ($query) {
                $query->where('from_id', $this->character->character_id)
                    ->orWhere('to_id', $this->character->character_id);
            })
            ->delete();

        $this->relationships = array_values(array_filter($this->relationships, fn ($r) => $r['id'] !== $relationshipId));
    }

    public function refreshRelationships(): void {
        $this->loadRelationships();
        $this->loadRelationshipTypes();
    }

    public function updatedNewImage() {
        $this->validate(['newImage' => 'image|max:2048']);

        if ($this->character->image_path) {
            Storage::disk('public')->delete($this->character->image_path);
        }

        $path = $this->newImage->store('characters', 'public');
        $this->character->update(['image_path' => $path]);
        $this->newImage = null;
    }

    public function removeImage() {
        if ($this->character->image_path) {
            Storage::disk('public')->delete($this->character->image_path);
            $this->character->update(['image_path' => null]);
        }
    }

    #[Renderless]
    public function addTag(string $name) {
        $name = mb_substr(trim($name), 0, 20);
        if ($name === '') return null;

        $tag = Hashtag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        $this->character->hashtags()->syncWithoutDetaching([$tag->hashtag_id]);

        return ['id' => $tag->hashtag_id, 'name' => $tag->name];
    }

    #[Renderless]
    public function removeTag(string $hashtagId) {
        $this->character->hashtags()->detach($hashtagId);
    }

    public function updated($property) {
        if (in_array($property, ['fullName', 'nickName', 'bio'])) {
            if ($property === 'fullName') {
                $this->fullName = mb_substr(ltrim($this->fullName), 0, 60);
                if (trim($this->fullName) === '') {
                    $this->fullName = 'New Character';
                }
            }

            if ($property === 'nickName') {
                $this->nickName = mb_substr(ltrim($this->nickName), 0, 20);

                if (trim($this->nickName) === '') {
                    $this->nickName = 'New Character';
                    $this->nickNameError = '';
                } else {
                    $isDuplicate = $this->project->characters()
                        ->where('nick_name', $this->nickName)
                        ->where('character_id', '!=', $this->character->character_id)
                        ->exists();
                    $this->nickNameError = $isDuplicate ? 'Nickname already taken.' : '';
                }
            }

            $this->character->update([
                'full_name' => $this->fullName,
                'nick_name' => $this->nickNameError ? $this->character->nick_name : $this->nickName,
                'bio' => $this->bio,
            ]);
            return;
        }

        if (str_starts_with($property, 'detailValues.')) {
            $fieldId = substr($property, strlen('detailValues.'));

            CharacterDetailValue::updateOrCreate(
                ['character_id' => $this->character->character_id, 'character_detail_field_id' => $fieldId],
                ['value' => $this->detailValues[$fieldId] ?? null]
            );
        }
    }

    public function deleteCharacter(): void {
        if ($this->character->image_path) {
            Storage::disk('public')->delete($this->character->image_path);
        }
        $this->character->delete();
        $this->redirect(route('projects.characters', $this->project), navigate: true);
    }
}; ?>

<div x-data="{ confirmingDelete: false }" class="p-6 lg:p-10 max-w-6xl mx-auto">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $project->title, 'url' => route('projects.show', $project), 'truncate' => true],
            ['label' => 'Characters', 'url' => route('projects.characters', $project)],
            ['label' => $character->nick_name, 'truncate' => true]
        ]" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-2 items-start">
        
        {{-- KOLOM KIRI: nama, backstory, dan detail group/field --}}
        <div class="lg:col-span-2 bg-brand-10 border border-brand-150 rounded-2xl p-8 flex flex-col gap-6 h-full">

            <div class="flex justify-between items-center w-full">
    
                <!-- Tombol Kiri (< Back) -->
                <a href="{{route('projects.characters', $project)}}"
                        class="flex items-center gap-1.5 text-app-feature text-text-60 hover:text-text-90 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back
                </a>

                <!-- Tombol Kanan (Trash / Delete) -->
                <button type="button" 
                        @click="confirmingDelete = true" 
                        class="p-1.5 text-danger-100/80 border hover:text-danger-100 hover:bg-danger-100/10 rounded-md transition-colors" 
                        title="Delete Character">
                    <!-- Menggunakan komponen ikon delete milikmu, ukurannya diperkecil agar proporsional untuk header -->
                    <x-icons.delete class="w-3 h-3 border-none"/>
                </button>

            </div>
            <div
                class="flex flex-col gap-1"
                x-data="{
                    editingNickName: false,
                    editingFullName: false,
                    nickNameDraft: @js($nickName),
                    nickNameDisplay: @js($nickName),
                    nickNameCount: {{ mb_strlen($nickName) }},
                    fullNameCount: {{ mb_strlen($fullName) }},
                    init() {
                        this.$wire.$watch('nickName', (value) => {
                            const displayValue = value === '' ? 'New Character' : value;
                            this.nickNameDisplay = displayValue;
                            this.nickNameCount = displayValue.length;
                        });
                    },
                    startEditNickName() {
                        this.nickNameDraft = this.nickNameDisplay;
                        this.nickNameCount = this.nickNameDraft.length;
                        this.editingNickName = true;
                        this.$nextTick(() => this.$refs.nickNameInput.focus());
                    },
                    cancelNickNameEdit() {
                        this.nickNameDraft = this.nickNameDisplay;
                        this.nickNameCount = this.nickNameDisplay.length;
                        this.editingNickName = false;
                    },
                    commitNickNameEdit() {
                        const typed = this.nickNameDraft.trim();
                        this.$wire.set('nickName', typed, true);

                        const displayValue = typed === '' ? 'New Character' : typed;
                        this.nickNameDraft = displayValue;
                        this.nickNameDisplay = displayValue;
                        this.nickNameCount = displayValue.length;
                        this.editingNickName = false;
                    },
                    stripLeadingSpace(e, countProp) {
                        if (e.target.value.startsWith(' ')) {
                            e.target.value = e.target.value.replace(/^\s+/, '');
                        }
                        this[countProp] = e.target.value.length;
                    },
                }"
            >
                <div class="flex items-end gap-2">
                    <h1
                        x-show="!editingNickName"
                        @click="startEditNickName()"
                        x-text="nickNameDisplay"
                        class="text-app-title-1 text-text-100 truncate cursor-text"
                    ></h1>

                    <div x-show="editingNickName" x-cloak class="flex items-center gap-2 flex-1 min-w-0 border-b {{ $nickNameError ? 'border-danger-100' : 'border-subtext-70' }}">
                        <input
                            type="text"
                            x-ref="nickNameInput"
                            x-model="nickNameDraft"
                            x-init="$el.addEventListener('input', (e) => stripLeadingSpace(e, 'nickNameCount'), true)"
                            @blur="commitNickNameEdit()"
                            @keydown.enter="$event.target.blur()"
                            @keydown.escape="$event.target.blur()"
                            maxlength="20"
                            placeholder="Nickname"
                            class="text-app-title-1 text-text-100 bg-transparent outline-none w-full truncate"
                        >
                        <button type="button" @mousedown.prevent @click="cancelNickNameEdit()" class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-text-60 hover:bg-black/10 hover:text-danger-100 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <span x-show="editingNickName" x-cloak class="text-app-desc-feature text-subtext-70">
                    <span x-text="nickNameCount"></span>/20
                </span>
                @if($nickNameError)
                    <span class="text-app-desc-feature text-danger-100">{{ $nickNameError }}</span>
                @endif
                <div class="flex items-center gap-2 text-app-body-medium text-subtext-90">
                    <span>Full Name :</span>
                    <input type="text" wire:model="fullName"
                        x-init="$el.addEventListener('input', (e) => stripLeadingSpace(e, 'fullNameCount'), true)"
                        @focus="editingFullName = true; fullNameCount = $event.target.value.length"
                        @blur="editingFullName = false; $wire.$commit()"
                        @keydown.enter="$event.target.blur()"
                        @keydown.escape="$event.target.blur()"
                        maxlength="60" placeholder="Full Name"
                        class="bg-transparent text-text-60 outline-none border-b border-transparent focus:border-subtext-70 transition-colors">
                    <span x-show="editingFullName" x-cloak class="text-app-desc-feature text-subtext-70"><span x-text="fullNameCount"></span>/60</span>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <h3 class="text-app-heading-2 text-text-80">Backstory</h3>
                <textarea wire:model.live.debounce.500ms="bio" @blur="$wire.$commit()" placeholder="Write their backstory..."
                    class="w-full bg-transparent outline-none text-text-60 text-app-body-medium resize-none [field-sizing:content] min-h-[50px]"></textarea>
            </div>

            <div class="border-t border-brand-150 pt-6 flex flex-col gap-6">
                <h3 class="text-app-feature text-secondary-200 tracking-wide flex items-center gap-2">
                    DETAILS
                    <button type="button" @click="window.dispatchEvent(new CustomEvent('open-edit-characters'))" class="text-text-80 hover:text-secondary-200 transition-colors">
                        <x-icons.rename class="w-3 h-3" />
                    </button>
                </h3>

                @forelse($detailGroups as $group)
                    <div wire:key="group-{{ $group['id'] }}" class="flex flex-col gap-3 {{ $loop->first ? '' : 'border-t border-brand-150 pt-6' }}">
                        <h4 class="text-app-heading-2 text-secondary-80 mb-2">{{ $group['name'] }}</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            @forelse($group['fields'] as $field)
                                <div wire:key="field-{{ $field['id'] }}" class="flex flex-col gap-1 text-left">
                                    <label class="text-app-feature text-text-70 truncate">{{ $field['name'] }}</label>
                                    <input type="text" wire:model.live.debounce.500ms="detailValues.{{ $field['id'] }}" @blur="$wire.$commit()" value="{{ $detailValues[$field['id']] ?? '' }}" placeholder="Enter value"
                                        class="w-full px-4 py-2 bg-bg-main border-1 border-secondary-100 rounded-lg focus:border-secondary-250 focus:border-2 outline-none transition-all text-subtext-100 text-app-body-medium placeholder:text-subtext-80">
                                </div>
                                 @empty
                                    <p class="text-subtext-90 font-medium text-app-feature">No field in this group yet.</p>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <p class="text-subtext-90 font-medium text-app-feature">No detail groups yet.</p>
                @endforelse
            </div>
        </div>

        {{-- KOLOM KANAN: foto, tag, relationship --}}
        <div class="bg-brand-10 border border-brand-150 rounded-2xl p-6 flex flex-col gap-6 h-full">
            <div class="flex flex-col gap-3">
                <h3 class="text-app-feature text-text-100">Character Image</h3>
                <div x-data class="relative aspect-[3/4] w-full rounded-lg bg-brand-100 overflow-hidden group cursor-pointer" @click="$refs.characterImageInput.click()">
                    @if($character->image_path)
                        <img src="{{ Storage::url($character->image_path) }}" class="absolute inset-0 w-full h-full object-cover">
                    @else
                        <div class="absolute inset-0 flex items-center justify-center text-subtext-80 text-app-desc-feature text-center px-4 border border-dashed border-brand-200 rounded-lg">
                            Click to upload (3:4)
                        </div>
                    @endif

                    <div class="absolute inset-0 bg-brand-200/60 backdrop-blur-[1.5px] flex flex-col items-center justify-center gap-2 text-text-70 opacity-0 group-hover:opacity-100 transition-all duration-300">
                        <span class="text-app-feature uppercase tracking-wider">Change Image</span>
                        @if($character->image_path)
                            <button type="button" wire:click.stop="removeImage" class="px-3 py-1 bg-text-70/70 hover:bg-text-70/90 text-subtext-60 text-app-desc-feature rounded-full transition-colors">Remove</button>
                        @endif
                    </div>

                    <input type="file" x-ref="characterImageInput" wire:model="newImage" accept="image/*" class="hidden">
                </div>
                @error('newImage') <span class="text-app-desc-feature text-danger-100">{{ $message }}</span> @enderror
            </div>

            <div class="flex flex-col gap-4 mb-4">
                <h3 class="text-app-feature text-text-100 pb-2 border-b border-brand-150">Tags</h3>
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
                            placeholder="New tag..."
                            class="pl-3 pr-11 py-2 rounded-full bg-brand-100 border border-secondary-100 outline-none text-app-body-small text-text-70 w-36"
                        >
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-app-desc-feature text-subtext-70 pointer-events-none" x-text="newTagName.length + '/20'"></span>
                    </div>
                    <button @click="showNewTagInput = false; newTagName = ''" type="button" class="w-5 h-5 rounded-full flex items-center justify-center text-text-60 hover:bg-black/10 hover:text-danger-100 transition-colors">
                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
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
                    <template x-for="tag in tags" :key="tag.id">
                        <span class="flex items-center gap-2 pl-4 pr-2 py-1.5 rounded-full bg-brand-100 text-app-body-small text-text-80 max-w-full">
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
                    <h3 class="text-app-feature text-text-100">Relationship</h3>
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
                                <span x-text="selectedChar ? selectedChar.name : 'Select character...'"></span>
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
                                <div x-show="characters.length === 0" class="px-3 py-2 text-app-desc-feature text-subtext-70 italic">No other characters</div>
                            </div>
                        </div>

                        {{-- Relationship type dropdown --}}
                        <div class="relative" @click.away="typeOpen = false">
                            <button type="button" @click="typeOpen = !typeOpen"
                                class="w-full px-3 py-2 bg-bg-main border border-brand-150 rounded-lg text-app-desc-feature text-left flex items-center justify-between gap-2 outline-none transition-colors hover:border-secondary-100"
                            >
                                <span x-show="!selectedType" class="text-subtext-70">Select type...</span>
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
                                <div x-show="types.length === 0" class="px-3 py-2 text-app-desc-feature text-subtext-70 italic">No types yet</div>
                                <div class="border-t border-brand-150">
                                    <div @click="typeOpen = false; window.dispatchEvent(new CustomEvent('open-relation-type-popup', { detail: { relationId: null, charFromName: '{{ $character->nick_name }}', charToName: selectedChar ? selectedChar.name : null, usedTypeIds: selectedChar ? (relatedTypesByChar[selectedChar.id] || []) : [] } }))"
                                        class="px-3 py-2 flex items-center gap-2 text-app-desc-feature font-semibold text-secondary-200 hover:bg-brand-100 cursor-pointer">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                        Add New Relation
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tipe baru dari popup → auto-pilih di dropdown --}}
                        {{-- Tipe dihapus dari popup → singkirkan dari list --}}

                        {{-- Action buttons --}}
                        <div class="flex gap-2">
                            <button wire:click="$set('showAddRelation', false)" type="button"
                                class="flex-1 py-2 rounded-md border border-secondary-50 text-app-desc-feature text-text-80 hover:bg-[#EAE1D5] transition-colors">
                                Cancel
                            </button>
                            <button wire:click="addRelationship" type="button"
                                :disabled="!selectedChar || !selectedType"
                                :class="(!selectedChar || !selectedType) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-brand-200'"
                                class="flex-1 py-2 rounded-md bg-brand-150 text-app-desc-feature text-text-80 transition-colors">
                                Add
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
                        <p class="text-app-desc-feature text-subtext-90">No relationships yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div x-show="confirmingDelete" style="display:none;" class="fixed inset-0 z-50 flex items-center justify-center bg-text-80/50 backdrop-blur-[1.5px]">
        <div @click.away="confirmingDelete = false" class="bg-bg-main border border-brand-100 rounded-xl shadow-xl p-6 w-full max-w-sm flex flex-col gap-5">
            <div class="text-center">
                <h3 class="text-app-heading-2 text-text-80">Delete Character?</h3>
                <p class="text-app-desc-feature text-text-70 mt-2">"{{ $nickName }}" and every relationship involving them will be permanently removed.</p>
            </div>
            <div class="flex gap-3">
                <button @click="confirmingDelete = false" class="flex-1 py-2.5 rounded-lg border border-brand-200 text-app-feature text-text-80 hover:bg-brand-100 transition-colors">Cancel</button>
                <button wire:click="deleteCharacter" class="flex-1 py-2.5 rounded-lg bg-danger-100 text-app-feature text-bg-main hover:bg-danger-100/90 transition-colors">Confirm Delete</button>
            </div>
        </div>
    </div>

    <livewire:projects.relation-type-popup :project="$project" wire:key="rel-type-popup" />
    <livewire:projects.character-details-popup :project="$project" wire:key="char-details-popup" />
</div>
