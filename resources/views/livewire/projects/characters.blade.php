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
        $this->project->seedDefaultCharacterDetailGroups();
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

    public function deleteCharacter($characterId) {
        // Relationship, hashtag, dan detail value milik karakter ini ikut terhapus (cascadeOnDelete di migration)
        $this->project->characters()->where('character_id', $characterId)->delete();
        $this->characters = array_values(array_filter($this->characters, fn ($character) => $character['id'] !== $characterId));
        $this->relationships = array_values(array_filter($this->relationships, fn ($relationship) => $relationship['from'] !== $characterId && $relationship['to'] !== $characterId));
    }

    public function createRelationship($fromId, $toId, $relationshipTypeId) {
        $relationship = Relationship::create([
            'from_id' => $fromId,
            'to_id' => $toId,
            'relationship_type_id' => $relationshipTypeId,
        ]);
        $relationship->load('relationshipType');

        return $this->mapRelationship($relationship);
    }



}; ?>

<div>
    <div class="p-6 lg:p-10 max-w-7xl mx-auto">
        <header class="flex justify-between items-center mb-10">
            <div class="flex items-center gap-3 text-app-heading-2 text-text-80">
                <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-secondary-200 transition-colors">Dashboard</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ route('projects.show', $project) }}" wire:navigate class="hover:text-secondary-200 transition-colors truncate">{{ $project->title }}</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-text-100 font-semibold">Characters</span>
            </div>
            <x-logo class="h-8 w-auto text-text-100" />
        </header>

        <div class="flex justify-between items-center mb-4">
            <h1 class="text-app-title-1 text-text-100">Characters Sheet</h1>

            <button
                @click="window.dispatchEvent(new CustomEvent('open-edit-characters'))"
                class="flex items-center gap-4 text-web-button text-[var(--color-text-60)] p-2 rounded hover:bg-[var(--color-brand-50)] hover:text-[var(--color-secondary-200)] transition-colors"
            >
                Edit Character Details
                <x-icons.rename class="w-4 h-4 stroke-2 group-hover:text-[var(--color-secondary-200)] transition-colors" />
            </button>
        </div>

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
            @wheel.prevent="onWheel($event)"
            @mousedown="startPan($event)"
            @mousemove="onPan($event); onDragChar($event)"
            @mouseup="stopPan(); stopDragChar()"
            @mouseleave="stopPan(); stopDragChar()"
            :style="`background-image: radial-gradient(circle, #C9BBA3 ${1.5 * zoom}px, transparent ${1.5 * zoom}px); background-size: ${22 * zoom}px ${22 * zoom}px; background-position: ${panX}px ${panY}px;`"
            :class="isAnyPopupOpen() ? 'cursor-auto' : (panning ? 'cursor-grabbing' : 'cursor-grab')"
            class="relative w-full h-[620px] rounded-xl border border-[#D5C6A9] bg-[#F5EFE9] overflow-hidden"
            wire:ignore
        >
            <div x-ref="canvas" class="absolute inset-0 origin-top-left" :style="`transform: translate(${panX}px, ${panY}px) scale(${zoom}); width: 2400px; height: 1800px;`">

                {{-- Garis relasi (di belakang karakter), tetap tampil selama sesi --}}
                {{-- Pakai div yang diputar (bukan SVG), karena <template x-for> tidak bisa
                     meng-clone elemen SVG dengan namespace yang benar. Posisi dihitung ulang
                     tiap render lewat relationLine(), jadi ikut bergerak saat karakter di-drag --}}
                <template x-for="rel in relationships" :key="'line-' + rel.id">
                    <div
                        class="absolute z-10 cursor-pointer"
                        :style="`left: ${relationLine(rel).x1}px; top: ${relationLine(rel).y1 - 7}px; width: ${relationLine(rel).length}px; height: 14px; transform-origin: left center; transform: rotate(${relationLine(rel).angle}rad);`"
                        @click="openEditRelation(rel)"
                    >
                        <div class="absolute inset-x-0 top-1/2 -translate-y-1/2 h-[2px]" :style="`background-color: ${rel.textColor};`"></div>
                    </div>
                </template>

                {{-- Posisi karakter dibuat reaktif (bukan dari Blade statis) supaya bisa di-drag --}}
                <template x-for="char in characters" :key="char.id">
                    <div
                        x-data="{ hoverSelf: false }"
                        @mouseenter="hoverSelf = true"
                        @mouseleave="hoverSelf = false"
                        @mousedown.stop="startDragChar($event, char)"
                        @click="if (!dragMoved) { if (addingRelation && relationSourceId !== char.id) { selectTarget(char.id) } else if (!addingRelation) { openCharacterInfo(char) } }"
                        :data-character-id="char.id"
                        class="absolute flex flex-col items-center gap-2 pt-11 px-6 select-none"
                        :class="draggingId === char.id ? 'cursor-grabbing z-40' : 'cursor-grab z-20'"
                        :style="`top: ${char.top - 44}px; left: ${char.left - 24}px;`"
                    >
                        <div class="relative w-20 h-20">
                            {{-- Tombol Add Relation, muncul saat hover & belum dalam mode pilih target --}}
                            <button
                                x-show="hoverSelf && !addingRelation"
                                @click.stop="startAddRelation(char.id)"
                                class="absolute -top-9 left-1/2 -translate-x-1/2 whitespace-nowrap px-2.5 py-1 rounded-full bg-brand-150 border border-[#D5C6A9] text-[11px] font-semibold text-[#4A4A4A] hover:bg-brand-200 transition-colors shadow-sm"
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
                        <span class="text-[13px] font-semibold text-text-100" x-text="char.name"></span>
                    </div>
                </template>

                {{-- Label nama relasi, dirender terpisah supaya tidak pernah tertutup garis,
                     tapi z-index-nya di bawah semua karakter supaya karakter selalu tampil di atas label --}}
                <template x-for="rel in relationships" :key="'label-' + rel.id">
                    <span
                        class="absolute z-[15] cursor-pointer whitespace-nowrap text-[12px] font-bold px-1.5 py-0.5 rounded"
                        :style="`left: ${relationLine(rel).midX}px; top: ${relationLine(rel).midY}px; transform: translate(-50%, -50%) rotate(${relationLine(rel).labelAngle}rad); color: ${rel.textColor}; background-color: ${rel.bgColor};`"
                        @click="openEditRelation(rel)"
                        x-text="rel.name"
                    ></span>
                </template>
            </div>

            {{-- Notifikasi mengambang: pilih karakter tujuan relasi --}}
            <div
                x-show="addingRelation"
                style="display: none;"
                class="absolute top-4 inset-x-0 z-40 flex justify-center"
            >
                <div class="flex items-center gap-3 bg-white border border-[#D5C6A9] rounded-full shadow-md px-4 py-2">
                    <span class="text-[13px] font-semibold text-[#4A4A4A]">Choose one character</span>
                    <button @click="cancelAddRelation()" class="text-[12px] font-bold text-secondary-200 hover:text-secondary-300 transition-colors px-2 py-1 rounded-full hover:bg-brand-100">Cancel</button>
                </div>
            </div>

            @include('livewire.projects.partials.character-info-popup')

            {{-- Kontrol Zoom --}}
            <div class="absolute bottom-5 left-5 z-30 flex flex-col bg-white border border-[#D5C6A9] rounded-lg shadow-sm overflow-hidden">
                <button @click="zoomIn()" class="w-9 h-9 flex items-center justify-center text-[#4A4A4A] hover:bg-[#EAE1D5] transition-colors border-b border-[#D5C6A9]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                </button>
                <span class="text-[11px] text-center text-[#7A7A7A] py-1" x-text="Math.round(zoom * 100) + '%'"></span>
                <button @click="zoomOut()" class="w-9 h-9 flex items-center justify-center text-[#4A4A4A] hover:bg-[#EAE1D5] transition-colors border-t border-[#D5C6A9]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/></svg>
                </button>
            </div>

            {{-- Tombol Tambah Karakter --}}
            <div x-data="{ hoverAdd: false }" class="absolute bottom-5 right-5 z-30">
                <span
                    x-show="hoverAdd"
                    x-transition
                    class="absolute -top-9 right-0 whitespace-nowrap px-2.5 py-1 rounded-full bg-brand-150 border border-[#D5C6A9] text-[11px] font-semibold text-[#4A4A4A] shadow-sm"
                >
                    Add New Character
                </span>
                <button
                    @mouseenter="hoverAdd = true"
                    @mouseleave="hoverAdd = false"
                    wire:click="addCharacter"
                    class="w-12 h-12 rounded-full bg-brand-150 border border-[#D5C6A9] text-[#4A4A4A] flex items-center justify-center hover:bg-brand-200 transition-colors shadow-sm"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                </button>
            </div>
        </div>
    </div>

    <livewire:projects.relation-type-popup :project="$project" />
    <livewire:projects.character-details-popup :project="$project" />
</div>
