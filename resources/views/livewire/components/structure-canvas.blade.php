<?php
use Livewire\Volt\Component;
use App\Models\Project;
use App\Models\ChapterCard; // Tambahkan ini
use App\Models\Manuscript;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

new class extends Component {
    public Project $project;
    public int $activeSectionIndex = 0; 

    #[On('template-applied')]
    public function resetIndex() {
        $this->activeSectionIndex = 0;
    }

    #[Computed]
    public function currentTemplate() {
        if (!$this->project->template_id) {
            return null;
        }

        return $this->project->template()->with('sections')->first();
    }

    public function nextSection() {
        if (!$this->currentTemplate) return;

        if ($this->activeSectionIndex < $this->currentTemplate->sections->count() - 1) {
            $this->activeSectionIndex++;
        }
    }

    public function prevSection() {
        if ($this->activeSectionIndex > 0) {
            $this->activeSectionIndex--;
        }
    }

    public function addChapter() {
        if (!$this->currentTemplate) return;

        $sectionId = $this->currentTemplate->sections[$this->activeSectionIndex]->structure_section_id;

        $lastOrder = ChapterCard::where('project_id', $this->project->project_id)
            ->max('order_index');

        $newOrder = $lastOrder ? $lastOrder + 1 : 1;

        $chapter = ChapterCard::create([
            'project_id' => $this->project->project_id,
            'structure_section_id' => $sectionId,
            'title' => 'Untitled Chapter',
            'status' => 'In Progress',
            'order_index' => $newOrder
        ]);

        Manuscript::create([
            'chapter_card_id' => $chapter->chapter_card_id, 
            'content' => '',
            'word_count' => 0
        ]);
    }
}; ?>

<div class="h-full flex flex-col p-8 relative overflow-hidden">
    <div class="flex items-center justify-between mb-8 transition-all duration-1000 shrink-0">
        <div class="w-10">
            @if($activeSectionIndex > 0)
                <button wire:click="prevSection" class="p-2 rounded-full hover:bg-brand-50 transition-transform active:scale-95">
                    <x-icons.chevron rotate="180" color="text-text-80" />
                </button>
            @endif
        </div>

        <h2 class="text-web-heading-2 text-text-90 font-merriweather transition-all duration-300 ease-in-out opacity-100 translate-x-0">
            {{ $this->currentTemplate->sections[$activeSectionIndex]->title }}
        </h2>

        <div class="w-10 flex justify-end">
            @if($activeSectionIndex < $this->currentTemplate->sections->count() - 1)
                <button wire:click="nextSection" class="p-2 rounded-full hover:bg-brand-50 transition-transform active:scale-95">
                    <x-icons.chevron rotate="0" color="text-text-80" />
                </button>
            @endif
        </div>
    </div>

    <div class="flex-1 overflow-y-auto overflow-x-hidden custom-scrollbar pb-24 py-8 px-5" 
         wire:key="section-{{ $this->currentTemplate->sections[$activeSectionIndex]->section_id }}">
    
        <div x-data 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="transform translate-y-10 opacity-0"
             x-transition:enter-end="transform translate-y-0 opacity-100"
             class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8 justify-items-center">
            
            @forelse($project->chapterCards()
                ->where('structure_section_id', $this->currentTemplate->sections[$activeSectionIndex]->structure_section_id)
                ->get() as $chapter)
                
                <div class="w-full flex justify-center">
                    <x-chapter-card :chapter="$chapter" />
                </div>
            @empty
                <div class="col-span-1 lg:col-span-2 xl:col-span-3 w-full h-64 flex items-center justify-center border-2 border-dashed border-brand-100 rounded-lg text-text-60 bg-bg-main/50">
                    <div class="flex flex-col items-center gap-2">
                        <x-icons.no-structure class="w-10 h-10 opacity-50" />
                        <span>No chapters in this section yet.</span>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <button wire:click="addChapter" wire:loading.attr="disabled" class="absolute bottom-8 right-8 z-10 w-14 h-14 bg-secondary-100 rounded-full flex items-center justify-center shadow-xl hover:bg-secondary-200 hover:-translate-y-1 transition-all duration-200 disabled:opacity-50 disabled:hover:translate-y-0 disabled:cursor-not-allowed border-1 border-bg-main">
        <div wire:loading wire:target="addChapter" class="animate-spin rounded-full h-6 w-6 border-b-2 border-white"></div>
        <div wire:loading.remove wire:target="addChapter">
            <x-icons.add-default class="text-white w-6 h-6" />
        </div>
    </button>
</div>