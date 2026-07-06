<?php

use Livewire\Volt\Component;
use App\Models\Project;
use App\Models\Character;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\Hashtag;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Renderless;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    public Project $project;

    public array $characters = [];
    public array $relationships = [];
    public array $relationshipTypes = [];

    public function mount(Project $project) {
        $this->project = $project;
        $this->project->seedDefaultRelationshipTypes();
        $this->loadCharacters();
        $this->loadRelationshipTypes();
        $this->loadRelationships();
    }

    private function loadCharacters() {
        $this->characters = $this->project->characters()->with('hashtags')->get()->map(fn (Character $character) => $this->mapCharacter($character))->all();
    }

    private function loadRelationshipTypes() {
        $this->relationshipTypes = $this->project->relationshipTypes()->get()->map(fn (RelationshipType $type) => $this->mapRelationshipType($type))->all();
    }

    private function loadRelationships() {
        $characterIds = $this->projectCharacterIds();

        $this->relationships = Relationship::with('relationshipType')->where(function ($query) use ($characterIds) {
            $query->whereIn('from_id', $characterIds)->orWhereIn('to_id', $characterIds);
        })->get()->map(fn (Relationship $relationship) => $this->mapRelationship($relationship))->all();
    }

    private function mapRelationship(Relationship $relationship): array {
        return [
            'id' => $relationship->relationship_id,
            'from' => $relationship->from_id,
            'to' => $relationship->to_id,
            'typeId' => $relationship->relationship_type_id,
            'name' => $relationship->relationshipType->name,
            'textColor' => $relationship->relationshipType->text_color,
            'bgColor' => $relationship->relationshipType->bg_color,
            'curveOffset' => $relationship->curve_offset,
        ];
    }

    private function projectCharacterIds() {
        return $this->project->characters()->pluck('character_id');
    }

    private function mapCharacter(Character $character): array {
        return [
            'id' => $character->character_id,
            'name' => $character->nick_name,
            'fullName' => $character->full_name,
            'bio' => $character->bio,
            'imagePath' => $character->image_path ? Storage::url($character->image_path) : null,
            'tags' => $character->hashtags->pluck('name')->all(),
            'top' => $character->canvas_top,
            'left' => $character->canvas_left,
        ];
    }

    private function mapRelationshipType(RelationshipType $type): array {
        return [
            'id' => $type->relationship_type_id,
            'name' => $type->name,
            'textColor' => $type->text_color,
            'bgColor' => $type->bg_color,
        ];
    }

    public function addCharacter() {
        $character = $this->project->characters()->create([
            'full_name' => 'New Character',
            'nick_name' => 'New Character',
            'bio' => '',
            'canvas_top' => 860 + rand(-60, 60),
            'canvas_left' => 1160 + rand(-60, 60),
        ]);

        $mapped = $this->mapCharacter($character);
        $this->characters[] = $mapped;
        $this->dispatch('character-created', character: $mapped);
    }

    public function updateCharacterPosition($id, $top, $left) {
        $this->project->characters()->where('character_id', $id)->update([
            'canvas_top' => (int) round($top),
            'canvas_left' => (int) round($left),
        ]);
    }

    public function updateRelationshipCurve($id, $offset) {
        $characterIds = $this->projectCharacterIds();
        Relationship::where('relationship_id', $id)
            ->where(fn ($q) => $q->whereIn('from_id', $characterIds)->orWhereIn('to_id', $characterIds))
            ->update(['curve_offset' => $offset]);
    }

    public function deleteCharacter($characterId) {
        // Relationship, hashtag, dan detail value milik karakter ini ikut terhapus (cascadeOnDelete di migration)
        $this->project->characters()->where('character_id', $characterId)->delete();
        $this->characters = array_values(array_filter($this->characters, fn ($character) => $character['id'] !== $characterId));
        $this->relationships = array_values(array_filter($this->relationships, fn ($relationship) => $relationship['from'] !== $characterId && $relationship['to'] !== $characterId));
    }

    public function createRelationship($fromId, $toId, $relationshipTypeId) {
        $duplicate = Relationship::where('relationship_type_id', $relationshipTypeId)
            ->where(function ($query) use ($fromId, $toId) {
                $query->where(fn ($q) => $q->where('from_id', $fromId)->where('to_id', $toId))
                    ->orWhere(fn ($q) => $q->where('from_id', $toId)->where('to_id', $fromId));
            })
            ->exists();

        if ($duplicate) {
            return null;
        }

        $relationship = Relationship::create([
            'from_id' => $fromId,
            'to_id' => $toId,
            'relationship_type_id' => $relationshipTypeId,
        ]);
        $relationship->load('relationshipType');

        return $this->mapRelationship($relationship);
    }



}; ?>

<div class="px-6 pt-6 lg:px-10 lg:pt-10 max-w-7xl mx-auto h-screen flex flex-col overflow-hidden">
    <div class="mb-4">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $project->title, 'url' => route('projects.show', $project), 'truncate' => true],
            ['label' => 'Characters']
        ]" />

        <div class="flex justify-between items-center">
            <h1 class="text-app-title-1 text-text-100">Characters Sheet</h1>
        </div>
    </div>

    <div class="flex-1 min-h-0 pb-6 lg:pb-10">
        {{-- WHITEBOARD --}}
        <div
            x-data="whiteboard(
                @js($project->project_id),
                @js($characters),
                @js($relationships),
                @js($relationshipTypes)
            )"
            x-on:livewire:navigated.window="centerBoard()"
            x-on:character-created.window="characters.push($event.detail.character)"
            @wheel.prevent="if (characters.length > 0) onWheel($event)"
            @mousedown="if (characters.length > 0) startPan($event)"
            @mousemove="if (characters.length > 0) { onPan($event); onDragChar($event); onDragLabel($event); }"
            @mouseup="stopPan(); stopDragChar(); stopDragLabel()"
            @mouseleave="stopPan(); stopDragChar(); stopDragLabel()"
            :style="`background-image: radial-gradient(circle, var(--color-brand-150) ${1.5 * zoom}px, transparent ${1.5 * zoom}px); background-size: ${22 * zoom}px ${22 * zoom}px; background-position: ${panX}px ${panY}px;`"
            :class="characters.length === 0 ? 'cursor-default' : (isAnyPopupOpen() ? 'cursor-auto' : (panning ? 'cursor-grabbing' : 'cursor-grab'))"
            class="relative w-full h-full rounded-xl border border-brand-200 bg-brand-10 overflow-hidden"
            wire:ignore
        >
            <div x-ref="canvas" class="absolute inset-0 origin-top-left" :style="`transform: translate(${panX}px, ${panY}px) scale(${zoom}); width: 2400px; height: 1800px;`">

                {{-- Garis relasi (di belakang karakter), tetap tampil selama sesi --}}
                {{-- Digambar sebagai SVG <path> lewat x-html (bukan <template x-for>),
                     karena Alpine tidak bisa meng-clone elemen SVG dengan namespace yang
                     benar lewat template cloning. Melengkung otomatis kalau ada lebih dari
                     1 relasi antara pasangan karakter yang sama, supaya tidak tumpang tindih.
                     Posisi dihitung ulang tiap render lewat relationLine(), jadi ikut
                     bergerak saat karakter di-drag. --}}
                <svg
                    class="absolute inset-0 z-10 pointer-events-none"
                    :width="canvasW" :height="canvasH"
                    style="overflow: visible;"
                    @click="onEdgeClick($event)"
                    x-html="edgePathsMarkup()"
                ></svg>

                {{-- Posisi karakter dibuat reaktif (bukan dari Blade statis) supaya bisa di-drag --}}
                <template x-for="char in characters" :key="char.id">
                    <div
                        x-data="{ hoverSelf: false }"
                        @mouseenter="hoverSelf = true"
                        @mouseleave="hoverSelf = false"
                        @mousedown.stop="startDragChar($event, char)"
                        @click="if (!dragMoved) { if (addingRelation && relationSourceId !== char.id) { selectTarget(char.id) } else if (!addingRelation) { openCharacterInfo(char) } }"
                        :data-character-id="char.id"
                        class="absolute cursor-pointer flex flex-col items-center gap-2 pt-11 px-6 select-none"
                        :class="draggingId === char.id ? 'cursor-grabbing z-40' : 'cursor-grab z-20'"
                        :style="`top: ${char.top - 44}px; left: ${char.left - 24}px;`"
                    >
                        <div class="relative w-20 h-20">
                            <button
                                x-show="hoverSelf && !addingRelation"
                                @click.stop="startAddRelation(char.id)"
                                class=" cursor-pointer absolute -top-10 left-1/2 -translate-x-1/2 whitespace-nowrap p-2 rounded-full bg-brand-150 border border-brand-100 text-app-desc-feature font-semibold text-text-80 hover:bg-brand-200 transition-colors shadow-sm"
                            >
                                + Add Relation
                            </button>

                            <div
                                class="w-20 h-20 rounded-full bg-brand-100 border-2 border-brand-200 overflow-hidden flex items-center justify-center transition-opacity"
                                :class="{
                                    'opacity-40': addingRelation && relationSourceId === char.id,
                                    'animate-pulse': addingRelation && relationSourceId !== char.id && !hoverSelf,
                                    'ring-2 ring-secondary-200': addingRelation && relationSourceId !== char.id
                                }"
                            >
                                <img x-show="char.imagePath" :src="char.imagePath" draggable="false" @dragstart.prevent class="w-full h-full object-cover pointer-events-none select-none">
                                <x-icons.default-avatar x-show="!char.imagePath" class="w-full h-full" />
                            </div>
                        </div>
                        <span class="text-app-caption font-semibold max-w-20 text-text-80 border border-brand-100 bg-brand-100 px-2 py-1 rounded truncate" x-text="char.name"></span>
                    </div>
                </template>

                {{-- Label nama relasi, dirender terpisah supaya tidak pernah tertutup garis,
                     tapi z-index-nya di bawah semua karakter supaya karakter selalu tampil di atas label.
                     Bisa di-drag tegak lurus terhadap garis A-B buat atur bentuk kurvanya sendiri
                     (disimpan per-relasi lewat updateRelationshipCurve, lihat curveOffset). --}}
                <template x-for="rel in relationships" :key="'label-' + rel.id">
                    <span
                        class="absolute z-[15] text-app-desc-feature font-semibold whitespace-nowrap px-2 py-1 rounded select-none"
                        :class="draggingLabelRelId === rel.id ? 'cursor-grabbing' : 'cursor-grab'"
                        :style="`left: ${relationLine(rel).midX}px; top: ${relationLine(rel).midY}px; transform: translate(-50%, -50%) rotate(${relationLine(rel).labelAngle}rad); color: ${rel.textColor}; background-color: ${rel.bgColor};`"
                        @mousedown.stop="startDragLabel($event, rel)"
                        @click="if (!labelDragMoved) openEditRelation(rel)"
                        x-text="rel.name"
                    ></span>
                </template>
            </div>

            {{-- Empty state: tampil saat belum ada karakter --}}
            <div
                x-show="characters.length === 0"
                style="display: none;"
                class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-4 pointer-events-none"
            >
                <div class="w-40 h-40">
                    <x-icons.no-character class="w-full h-full" />
                </div>
                <div class="text-center">
                    <p class="text-app-heading-2 text-secondary-150">No characters yet</p>
                    <p class="text-app-feature text-medium text-secondary-100 mt-1">Click the <span class="font-bold">+</span> button below to add your first character</p>
                </div>
            </div>

            {{-- Notifikasi mengambang: pilih karakter tujuan relasi --}}
            <div
                x-show="addingRelation"
                style="display: none;"
                class="absolute top-4 inset-x-0 z-40 flex justify-center"
            >
                <div class="flex items-center gap-3 bg-bg-main border border-secondary-100 rounded-full shadow-sm px-5 py-2">
                    <span class="text-app-feature text-text-80">Choose one character</span>
                    <button @click="cancelAddRelation()" class="text-app-feature text-danger-100 transition-colors px-4 py-1 rounded-full hover:bg-danger-100/10">Cancel</button>
                </div>
            </div>

            @include('livewire.projects.partials.character-info-popup')

            {{-- Kontrol Zoom --}}
            <div class="absolute bottom-5 left-5 z-30 flex flex-col bg-white border border-brand-150 px-1 rounded-lg shadow-sm overflow-hidden">
                <button @click="zoomIn()" class="w-9 h-9 flex items-center justify-center text-text-80 hover:bg-bg-main transition-colors border-b border-secondary-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                </button>
                <span class="text-app-desc-feature text-center text-text-80 py-2" x-text="Math.round(zoom * 100) + '%'"></span>
                <button @click="zoomOut()" class="w-9 h-9 flex items-center justify-center text-text-80 hover:bg-bg-main transition-colors border-t border-secondary-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/></svg>
                </button>
            </div>

            {{-- Tombol Tambah Karakter --}}
            <div x-data="{ hoverAdd: false }" class="absolute bottom-5 right-5 z-30">
                <span
                    x-show="hoverAdd"
                    x-transition
                    class="absolute -top-11 right-0 whitespace-nowrap p-2 rounded-md bg-text-100/60 text-app-caption font-semibold text-bg-main shadow-sm"
                >
                    Add New Character
                </span>
                <button 
                    @mouseenter="hoverAdd = true"
                    @mouseleave="hoverAdd = false"
                    wire:click="addCharacter"
                    class="w-12 h-12 bg-secondary-100 rounded-full flex items-center justify-center shadow-xl hover:bg-secondary-200 hover:-translate-y-1 transition-all duration-200 border-1 border-bg-main">
                    
                    <x-icons.add class="text-white w-4 h-4" />

                </button>
            </div>
        </div>
    </div>

    <livewire:projects.relation-type-popup :project="$project" />
</div>

