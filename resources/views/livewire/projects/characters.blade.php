<?php

use Livewire\Volt\Component;
use App\Models\Project;
use App\Models\Character;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\Hashtag;
use App\Helpers\TextHelper;
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
        $charName = TextHelper::uniqueName(
            'Unnamed Character',
            fn () => $this->project->characters()->pluck('full_name')
        );

        $charCount = count($this->characters);
        $isExpanding = ($charCount > 0) && ($charCount % 25 === 0);

        if ($isExpanding) {
            $this->project->characters()->update([
                'canvas_left' => \Illuminate\Support\Facades\DB::raw('canvas_left + 400'),
                'canvas_top' => \Illuminate\Support\Facades\DB::raw('canvas_top + 300'),
            ]);
            $this->loadCharacters();
            $this->dispatch('characters-shifted', shiftX: 400, shiftY: 300);
        }

        $multiplier = floor(count($this->characters) / 25);
        $currentW = 2400 + ($multiplier * 800);
        $currentH = 1800 + ($multiplier * 600);

        $character = $this->project->characters()->create([
            'full_name' => $charName,
            'nick_name' => $charName,
            'bio' => '',
            'canvas_top' => ($currentH / 2 - 60) + rand(-60, 60),
            'canvas_left' => ($currentW / 2 - 60) + rand(-60, 60),
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
            ['label' => __('Dashboard'), 'url' => route('dashboard')],
            ['label' => $project->title, 'url' => route('projects.show', $project), 'truncate' => true],
            ['label' => __('Characters')]
        ]" />

        <div class="flex justify-between items-center gap-4">
            <h1 class="text-app-title-1 text-text-100">{{ __('Characters') }}</h1>
            
            <div class="relative w-64" 
                 x-data="{ localQuery: '', showDropdown: false, recommendations: { characters: [], tags: [], relations: [] } }" 
                 @search-recommendations.window="recommendations = $event.detail"
                 @click.away="showDropdown = false"
            >
                <svg class="absolute left-3 top-2.5 w-4 h-4 text-subtext-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input 
                    x-model="localQuery"
                    @input="$dispatch('search-query-updated', {query: localQuery, type: 'all'}); showDropdown = true"
                    @focus="$dispatch('search-query-updated', {query: localQuery, type: 'all'}); showDropdown = true"
                    type="text" 
                    placeholder="{{ __('Search character...') }}" 
                    class="w-full pl-9 pr-8 py-2 bg-brand-10 border border-brand-150 rounded-full text-app-body-medium text-text-80 placeholder-subtext-100 focus:ring-1 focus:ring-secondary-150 outline-none transition-shadow"
                >
                <button x-show="localQuery !== ''" @click="localQuery = ''; $dispatch('search-query-updated', {query: '', type: 'all'}); showDropdown = false" class="absolute right-3 top-2.5 text-subtext-100 hover:text-text-80 transition-colors" x-cloak>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                <div x-show="showDropdown && localQuery !== ''" 
                     x-transition
                     class="absolute top-full left-0 right-0 mt-2 bg-brand-10 border border-brand-200 rounded-lg shadow-lg overflow-hidden z-[100] max-h-96 overflow-y-auto" 
                     x-cloak>
                    
                    <template x-if="recommendations.characters.length > 0">
                        <div>
                            <div class="px-4 py-1 bg-brand-50 text-app-caption font-semibold text-subtext-100 uppercase">{{ __('Characters') }}</div>
                            <template x-for="rec in recommendations.characters" :key="rec.id">
                                <button @click="localQuery = rec.name; $dispatch('search-query-updated', {query: rec.name, type: 'character'}); showDropdown = false" 
                                        class="w-full flex items-center gap-3 px-4 py-2 hover:bg-brand-150 transition-colors text-left">
                                    <div class="w-6 h-6 shrink-0 rounded-full bg-brand-100 border border-brand-200 overflow-hidden flex items-center justify-center">
                                        <img x-show="rec.imagePath" :src="rec.imagePath" class="w-full h-full object-cover">
                                        <x-icons.default-avatar x-show="!rec.imagePath" class="w-full h-full" />
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-app-body-medium text-text-80 truncate" x-text="rec.name"></span>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </template>

                    <template x-if="recommendations.tags.length > 0">
                        <div>
                            <div class="px-4 py-1 bg-brand-50 text-app-caption font-semibold text-subtext-100 uppercase">{{ __('Tags') }}</div>
                            <template x-for="tag in recommendations.tags" :key="tag">
                                <button @click="localQuery = tag; $dispatch('search-query-updated', {query: tag, type: 'tag'}); showDropdown = false" 
                                        class="w-full flex items-center gap-3 px-4 py-2 hover:bg-brand-150 transition-colors text-left">
                                    <svg class="w-4 h-4 text-secondary-100 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                    <span class="text-app-body-medium text-text-80 truncate" x-text="tag"></span>
                                </button>
                            </template>
                        </div>
                    </template>

                    <template x-if="recommendations.relations.length > 0">
                        <div>
                            <div class="px-4 py-1 bg-brand-50 text-app-caption font-semibold text-subtext-100 uppercase">{{ __('Relations') }}</div>
                            <template x-for="rel in recommendations.relations" :key="rel">
                                <button @click="localQuery = rel; $dispatch('search-query-updated', {query: rel, type: 'relation'}); showDropdown = false" 
                                        class="w-full flex items-center gap-3 px-4 py-2 hover:bg-brand-150 transition-colors text-left">
                                    <svg class="w-4 h-4 text-brand-200 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                    <span class="text-app-body-medium text-text-80 truncate" x-text="rel"></span>
                                </button>
                            </template>
                        </div>
                    </template>

                    <template x-if="recommendations.characters.length === 0 && recommendations.tags.length === 0 && recommendations.relations.length === 0">
                        <div class="px-4 py-4 text-center">
                            <p class="text-app-body-medium text-text-60">{{ __('No results found') }}</p>
                        </div>
                    </template>
                </div>
            </div>
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
            x-on:characters-shifted.window="characters.forEach(c => { c.left += $event.detail.shiftX; c.top += $event.detail.shiftY; }); panX -= $event.detail.shiftX * zoom; panY -= $event.detail.shiftY * zoom; clampPan();"
            @wheel.prevent="if (characters.length > 0) onWheel($event)"
            @mousedown="if (characters.length > 0) startPan($event)"
            @mousemove="if (characters.length > 0) { onPan($event); onDragChar($event); onDragLabel($event); }"
            @mouseup="stopPan(); stopDragChar(); stopDragLabel()"
            @mouseleave="stopPan(); stopDragChar(); stopDragLabel()"
            :class="characters.length === 0 ? 'cursor-default' : (isAnyPopupOpen() ? 'cursor-auto' : (panning ? 'cursor-grabbing' : 'cursor-grab'))"
            class="bg-dot-pattern relative w-full h-full rounded-xl border border-brand-200 bg-brand-10 overflow-hidden"
            wire:ignore
        >
            <div x-ref="canvas" class="absolute inset-0 origin-top-left" :style="`transform: translate(${panX}px, ${panY}px) scale(${zoom}); width: ${canvasW}px; height: ${canvasH}px;`">

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
                        class="absolute cursor-pointer flex flex-col items-center gap-2 pt-11 px-6 select-none transition duration-300"
                        :class="{
                            'cursor-grabbing z-40': draggingId === char.id,
                            'cursor-grab z-20': draggingId !== char.id && (!searchQuery || !hasSearchMatch || isCharacterHighlighted(char)),
                            'cursor-grab z-10': draggingId !== char.id && searchQuery && hasSearchMatch && !isCharacterHighlighted(char),
                            'scale-105 drop-shadow-xl z-30': searchQuery && hasSearchMatch && isCharacterHighlighted(char)
                        }"
                        :style="`top: ${char.top - 44}px; left: ${char.left - 24}px;`"
                    >
                        <div class="relative w-20 h-20">
                            <button
                                x-show="hoverSelf && !addingRelation"
                                @click.stop="startAddRelation(char.id)"
                                class=" cursor-pointer absolute -top-10 left-1/2 -translate-x-1/2 whitespace-nowrap p-2 rounded-full bg-brand-150 border border-brand-100 text-app-desc-feature font-semibold text-text-80 hover:bg-brand-200 transition-colors shadow-sm"
                            >
                                {{ __('+ Add Relation') }}
                            </button>

                            <div
                                class="w-20 h-20 rounded-full bg-brand-100 border-2 border-brand-200 overflow-hidden flex items-center justify-center transition-all duration-300"
                                :class="{
                                    'opacity-40': addingRelation && relationSourceId === char.id,
                                    'animate-pulse': addingRelation && relationSourceId !== char.id && !hoverSelf,
                                    'ring-2 ring-secondary-200': addingRelation && relationSourceId !== char.id,
                                    'ring-3 ring-secondary-150 border-transparent': searchQuery && hasSearchMatch && isCharacterHighlighted(char)
                                }"
                            >
                                <img x-show="char.imagePath" :src="char.imagePath" draggable="false" @dragstart.prevent class="w-full h-full object-cover pointer-events-none select-none">
                                <x-icons.default-avatar x-show="!char.imagePath" class="w-full h-full" />
                            </div>
                        </div>
                        <span class="text-app-caption font-semibold max-w-20 text-text-80 border border-brand-100 bg-brand-100 px-2 py-1 rounded truncate" x-text="char.name"></span>
                    </div>
                </template>

                <template x-for="rel in relationships" :key="'label-' + rel.id">
                    <span
                        class="absolute z-[15] text-app-desc-feature font-semibold whitespace-nowrap px-2 py-1 rounded select-none [transition-property:opacity,filter] duration-300"
                        :class="{
                            'cursor-grabbing': draggingLabelRelId === rel.id,
                            'cursor-grab': draggingLabelRelId !== rel.id,
                            'opacity-30 grayscale': searchQuery && hasSearchMatch && !isRelationHighlighted(rel)
                        }"
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
                    <p class="text-app-heading-2 text-secondary-150">{{ __('No characters yet') }}</p>
                    <p class="text-app-feature text-medium text-secondary-100 mt-1">{!! __('Click the <span class="font-bold">+</span> button below to add your first character') !!}</p>
                </div>
            </div>

            {{-- Notifikasi mengambang: pilih karakter tujuan relasi --}}
            <div
                x-show="addingRelation"
                style="display: none;"
                class="absolute top-4 inset-x-0 z-40 flex justify-center"
            >
                <div class="flex items-center gap-3 bg-bg-main border border-secondary-100 rounded-full shadow-sm px-5 py-2">
                    <span class="text-app-feature text-text-80">{{ __('Choose one character') }}</span>
                    <button @click="cancelAddRelation()" class="text-app-feature text-danger-100 transition-colors px-4 py-1 rounded-full hover:bg-danger-100/10">{{ __('Cancel') }}</button>
                </div>
            </div>

            @include('livewire.projects.partials.character-info-popup')

            {{-- Kontrol Zoom --}}
            <div class="absolute bottom-5 left-5 z-30 flex flex-col bg-card-bg border border-brand-200 px-1 rounded-lg shadow-md overflow-hidden">
                <button @click="zoomIn()" class="w-9 h-9 flex items-center justify-center text-text-80 hover:bg-brand-50 transition-colors border-b border-brand-200 cursor-pointer" title="{{ __('Zoom In') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                </button>
                <span class="text-app-desc-feature font-semibold text-center text-text-80 py-2" x-text="Math.round(zoom * 100) + '%'"></span>
                <button @click="zoomOut()" class="w-9 h-9 flex items-center justify-center text-text-80 hover:bg-brand-50 transition-colors border-t border-brand-200 cursor-pointer" title="{{ __('Zoom Out') }}">
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
                    {{ __('Add New Character') }}
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

