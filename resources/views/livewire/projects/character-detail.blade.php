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
use App\Traits\HandlesFileUpload;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads, HandlesFileUpload;

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
        $this->validate(['newImage' => 'image|max:5120']);

        $path = $this->replaceImage($this->newImage, $this->character->image_path, 'characters');
        $this->character->update(['image_path' => $path]);
        $this->newImage = null;
    }

    public function removeImage() {
        if ($this->character->image_path) {
            $this->deleteImage($this->character->image_path);
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
                    $this->fullName = 'Unnamed Character';
                }
            }

            if ($property === 'nickName') {
                $this->nickName = mb_substr(ltrim($this->nickName), 0, 30);

                if (trim($this->nickName) === '') {
                    $this->nickName = 'Unnamed Character';
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
                    {{ __('Back') }}
                </a>

                <!-- Tombol Kanan (Trash / Delete) -->
                <button type="button" @click="$dispatch('open-delete-character-confirm')" 
                        class="p-1.5 text-danger-100/80 border hover:text-danger-100 hover:bg-danger-100/10 rounded-md transition-colors" 
                        title="{{ __('Delete Character') }}">
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
                    nickNameError: '',
                    existingNicknames: @js(array_map(fn($c) => strtolower($c['name']), $otherCharacters)),
                    init() {
                        this.$wire.$watch('nickName', (value) => {
                            const displayValue = value === '' ? 'Unnamed Character' : value;
                            this.nickNameDisplay = displayValue;
                            this.nickNameCount = displayValue.length;
                        });
                    },
                    startEditNickName() {
                        this.nickNameDraft = this.nickNameDisplay;
                        this.nickNameCount = this.nickNameDraft.length;
                        this.nickNameError = '';
                        this.editingNickName = true;
                        this.$nextTick(() => this.$refs.nickNameInput.focus());
                    },
                    cancelNickNameEdit() {
                        this.nickNameDraft = this.nickNameDisplay;
                        this.nickNameCount = this.nickNameDisplay.length;
                        this.nickNameError = '';
                        this.editingNickName = false;
                    },
                    checkDuplicate() {
                        const typed = this.nickNameDraft.trim().toLowerCase();
                        if (this.existingNicknames.includes(typed) && typed !== this.nickNameDisplay.toLowerCase()) {
                            this.nickNameError = 'Nickname is already taken';
                        } else {
                            this.nickNameError = '';
                        }
                    },
                    commitNickNameEdit() {
                        this.checkDuplicate();
                        if (this.nickNameError) {
                            this.cancelNickNameEdit();
                            return;
                        }
                        const typed = this.nickNameDraft.trim();
                        this.$wire.set('nickName', typed, true);

                        const displayValue = typed === '' ? 'Unnamed Character' : typed;
                        this.nickNameDraft = displayValue;
                        this.nickNameDisplay = displayValue;
                        this.nickNameCount = displayValue.length;
                        this.editingNickName = false;
                    },
                    stripLeadingSpace(e, countProp) {
                        if (e.target.value.startsWith(' ')) {
                            e.target.value = e.target.value.replace(/^\s+/, '');
                        }
                        if (countProp === 'nickNameCount') {
                            this.nickNameDraft = e.target.value;
                            this.checkDuplicate();
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

                    <div x-show="editingNickName" x-cloak class="flex items-center gap-2 flex-1 min-w-0 border-b transition-colors" :class="nickNameError ? 'border-danger-100' : 'border-subtext-70'">
                        <input
                            type="text"
                            x-ref="nickNameInput"
                            x-model="nickNameDraft"
                            x-init="$el.addEventListener('input', (e) => stripLeadingSpace(e, 'nickNameCount'), true)"
                            @blur="commitNickNameEdit()"
                            @keydown.enter="$event.target.blur()"
                            @keydown.escape="$event.target.blur()"
                            maxlength="30"
                            placeholder="{{ __('Nickname') }}"
                            class="text-app-title-1 text-text-100 bg-transparent outline-none w-full truncate"
                        >
                        <button type="button" @mousedown.prevent @click="cancelNickNameEdit()" class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-text-60 hover:bg-black/10 hover:text-danger-100 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div x-show="editingNickName" x-cloak class="flex items-center justify-between text-app-desc-feature">
                    <span x-show="nickNameError" class="text-danger-100" x-text="nickNameError"></span>
                    <span class="text-subtext-80 ml-auto"><span x-text="nickNameCount"></span>/30</span>
                </div>
                @if($nickNameError)
                    <span x-show="!editingNickName" class="text-app-desc-feature text-danger-100">{{ $nickNameError }}</span>
                @endif
                <div class="flex items-center gap-2 text-app-body-medium text-subtext-90">
                    <span>Full Name :</span>
                    <input type="text" wire:model="fullName"
                        x-init="$el.addEventListener('input', (e) => stripLeadingSpace(e, 'fullNameCount'), true)"
                        @focus="editingFullName = true; fullNameCount = $event.target.value.length"
                        @blur="editingFullName = false; $wire.$commit()"
                        @keydown.enter="$event.target.blur()"
                        @keydown.escape="$event.target.blur()"
                        maxlength="60" placeholder="{{ __('Full Name') }}"
                        class="bg-transparent text-text-60 outline-none border-b border-transparent focus:border-subtext-70 transition-colors">
                    <span x-show="editingFullName" x-cloak class="text-app-desc-feature text-subtext-80"><span x-text="fullNameCount"></span>/60</span>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <h3 class="text-app-heading-2 text-text-80">{{ __('Backstory') }}</h3>
                <textarea wire:model.live.debounce.500ms="bio" @blur="$wire.$commit()" placeholder="{{ __('Write their backstory...') }}"
                    class="w-full bg-transparent outline-none text-text-60 text-app-body-medium resize-none [field-sizing:content] min-h-[50px]"></textarea>
            </div>

            @include('livewire.projects.partials.character-details-groups')
        </div>

        {{-- KOLOM KANAN: foto, tag, relationship --}}
        @include('livewire.projects.partials.character-info-section')
    </div>

    <x-confirm-dialog
        eventName="open-delete-character-confirm"
        title="{{ __('Delete Character?') }}"
        description='"{{ $nickName }}" and every relationship involving them will be permanently removed.'
        confirmText="Confirm Delete"
        cancelText="Cancel"
        submitAction="deleteCharacter"
    >
        <x-slot:icon>
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
        </x-slot:icon>
    </x-confirm-dialog>

    <livewire:projects.relation-type-popup :project="$project" wire:key="rel-type-popup" />
    <livewire:projects.character-details-popup :project="$project" wire:key="char-details-popup" />
</div>
