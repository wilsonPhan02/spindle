<?php
use Livewire\Volt\Component;
use App\Models\Project;
use App\Models\ChapterCard;
use App\Models\Manuscript;
use App\Helpers\TextHelper;
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

    #[Computed]
    public function groupedChapters() {
        return ChapterCard::where('project_id', $this->project->project_id)
            ->with('tags')
            ->orderBy('order_index')
            ->get()
            ->groupBy('structure_section_id');
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

        $sections = $this->currentTemplate->sections;
        $currentSection = $sections[$this->activeSectionIndex];
        $sectionId = $currentSection->structure_section_id;

        $maxInCurrentSection = ChapterCard::where('project_id', $this->project->project_id)
            ->where('structure_section_id', $sectionId)
            ->max('order_index');

        if ($maxInCurrentSection) {
            // Jika section sudah ada isinya, chapter baru ditaruh setelah chapter terakhir di section ini
            $newOrder = $maxInCurrentSection + 1;
        } else {
            // Jika section ini kosong, cari chapter terakhir dari section-section SEBELUMNYA
            $previousSectionIds = $sections->take($this->activeSectionIndex)->pluck('structure_section_id');
            
            $maxInPreviousSections = ChapterCard::where('project_id', $this->project->project_id)
                ->whereIn('structure_section_id', $previousSectionIds)
                ->max('order_index');
                
            $newOrder = $maxInPreviousSections ? $maxInPreviousSections + 1 : 1;
        }

        // GESER SEMUA CHAPTER KE BAWAH
        ChapterCard::where('project_id', $this->project->project_id)
            ->where('order_index', '>=', $newOrder)
            ->increment('order_index');

        // Buat Chapter Card & Manuscript
        $chapterTitle = TextHelper::uniqueName(
            'Untitled Chapter',
            fn () => ChapterCard::where('project_id', $this->project->project_id)->pluck('title')
        );

        $chapter = ChapterCard::create([
            'project_id' => $this->project->project_id,
            'structure_section_id' => $sectionId,
            'title' => $chapterTitle,
            'status' => 'In Progress',
            'order_index' => $newOrder
        ]);

        \App\Models\Manuscript::create([
            'chapter_card_id' => $chapter->chapter_card_id,
            'title' => 'Draft 1',
            'content' => '',
            'word_count' => 0
        ]);
    }

    public function deleteChapter($chapterId) {
        $chapter = ChapterCard::where('project_id', $this->project->project_id)->find($chapterId);
        
        if (!$chapter) return;

        $deletedOrder = $chapter->order_index;

        \App\Models\Manuscript::where('chapter_card_id', $chapterId)->delete(); 
        
        $chapter->delete();

        // GESER SEMUA CHAPTER KE ATAS
        ChapterCard::where('project_id', $this->project->project_id)
            ->where('order_index', '>', $deletedOrder)
            ->decrement('order_index');
    }

    #[On('move-chapter')]
    public function moveChapterToSection($chapterId, $targetSectionId)
    {
        DB::transaction(function () use ($chapterId, $targetSectionId) {
            $chapter = ChapterCard::where('project_id', $this->project->project_id)->find($chapterId);
            if (!$chapter) return;

            $oldOrder = $chapter->order_index;

            ChapterCard::where('project_id', $this->project->project_id)
                ->where('order_index', '>', $oldOrder)
                ->where('chapter_card_id', '!=', $chapterId)
                ->decrement('order_index');

            $maxInTarget = ChapterCard::where('project_id', $this->project->project_id)
                ->where('structure_section_id', $targetSectionId)
                ->where('chapter_card_id', '!=', $chapterId)
                ->max('order_index');

            if ($maxInTarget) {
                $newOrder = $maxInTarget + 1;
            } else {
                $sections = $this->currentTemplate->sections;
                $targetIndex = $sections->search(fn($s) => $s->structure_section_id == $targetSectionId);
                $prevSections = $sections->take($targetIndex)->pluck('structure_section_id');
                
                $maxPrev = ChapterCard::where('project_id', $this->project->project_id)
                                ->whereIn('structure_section_id', $prevSections)
                                ->where('chapter_card_id', '!=', $chapterId)
                                ->max('order_index');

                $newOrder = $maxPrev ? $maxPrev + 1 : 1;
            }

            ChapterCard::where('project_id', $this->project->project_id)
                ->where('order_index', '>=', $newOrder)
                ->where('chapter_card_id', '!=', $chapterId)
                ->increment('order_index');

            $chapter->update([
                'structure_section_id' => $targetSectionId,
                'order_index' => $newOrder
            ]);
        });

        $sections = $this->currentTemplate->sections;
        
        $targetIndex = $sections->search(fn($s) => $s->structure_section_id == $targetSectionId);
        
        if ($targetIndex !== false) {
            $this->activeSectionIndex = $targetIndex;
        }
    }

    public function updateChapterOrder($orderedIds)
    {
        $chapters = ChapterCard::where('project_id', $this->project->project_id)
            ->whereIn('chapter_card_id', $orderedIds)
            ->get();

        $availableIndexes = $chapters->pluck('order_index')->sort()->values()->toArray();

        DB::transaction(function () use ($orderedIds, $availableIndexes) {
            foreach ($orderedIds as $index => $chapterId) {
                ChapterCard::where('chapter_card_id', $chapterId)
                    ->update(['order_index' => $availableIndexes[$index]]);
            }
        });
    }
}; ?>

<div class="h-full flex flex-col p-8 px-5 relative overflow-hidden">
    <div class="flex items-center justify-between mb-3 transition-all duration-1000 shrink-0">
        <div class="w-10 self-start">
            @if($activeSectionIndex > 0)
                <button wire:click="prevSection" class="p-2 rounded-full hover:bg-brand-100 transition-transform active:scale-95">
                    <x-icons.chevron rotate="180" color="text-text-80" />
                </button>
            @endif
        </div>

        <h2 class="flex-1 text-center text-app-title-1 text-text-80 transition-all duration-300 ease-in-out opacity-100 translate-x-0">
            {{ $this->currentTemplate->sections[$activeSectionIndex]->title }}
        </h2>

        <div class="w-10 flex justify-end self-start">
            @if($activeSectionIndex < $this->currentTemplate->sections->count() - 1)
                <button wire:click="nextSection" class="p-2 rounded-full hover:bg-brand-100 transition-transform active:scale-95">
                    <x-icons.chevron rotate="0" color="text-text-80" />
                </button>
            @endif
        </div>
    </div>

    <div class="flex-1 flex overflow-hidden relative">
        @foreach($this->currentTemplate->sections as $index => $section)
            <div class="w-full h-full flex-shrink-0 transition-transform duration-500 ease-in-out overflow-y-auto overflow-x-hidden custom-scrollbar pb-24 px-5"
                 style="transform: translateX(-{{ $activeSectionIndex * 100 }}%);"
                 wire:key="section-{{ $section->structure_section_id }}">
            
                <div x-data="sortableList(@this)" x-ref="list"
                     class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 justify-items-center">
                    
                    @php
                        $chaptersForSection = $this->groupedChapters->get($section->structure_section_id, collect());
                    @endphp

                    @forelse($chaptersForSection as $chapter)
                        <div class="w-full flex justify-center sortable-item cursor-grab active:cursor-grabbing"
                             data-id="{{ $chapter->chapter_card_id }}"
                             wire:key="chapter-{{ $chapter->chapter_card_id }}">

                            <x-chapter-card
                                :chapter="$chapter"
                                :sections="$this->currentTemplate->sections"
                            />
                        </div>
                    @empty
                        <div class="col-span-full w-full min-h-40 flex items-center justify-center border-2 border-dashed border-brand-100 rounded-lg text-text-60 bg-transparent">
                            <div class="flex flex-col items-center justify-center gap-2 text-center w-full">
                                <x-icons.no-structure class="w-15 h-15 opacity-80" />
                                <span class="text-app-sub-feature">{{ __('No chapters in this section yet.') }}</span>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <button wire:click="addChapter" wire:loading.attr="disabled" class="absolute bottom-8 right-10 z-10 w-12 h-12 bg-secondary-100 rounded-full flex items-center justify-center shadow-xl hover:bg-secondary-200 hover:-translate-y-1 transition-all duration-200 disabled:opacity-50 disabled:hover:translate-y-0 disabled:cursor-not-allowed border-1 border-bg-main">
        <div wire:loading wire:target="addChapter" class="animate-spin rounded-full h-6 w-6 border-b-2 border-white"></div>
        <div wire:loading.remove wire:target="addChapter">
            <x-icons.add class="text-white w-4 h-4" />
        </div>
    </button>

    <x-confirm-dialog
        eventName="open-delete-dialog"
        title="{{ __('Delete Chapter') }}"
        description="Are you sure you want to delete this chapter? This action will shift the order of subsequent chapters and cannot be undone."
        confirmText="Yes, Delete"
        cancelText="Cancel"
        submitAction="deleteChapter"
    >
        <x-slot:icon>
            <x-icons.delete class="w-10 h-10" />
        </x-slot:icon>
    </x-confirm-dialog>
</div>


