<?php
use Livewire\Volt\Component;
use App\Models\Template;
use App\Models\Project;
use App\Models\ChapterCard;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public bool $isOpen = false;
    public int $step = 1; 
    public ?string $selectedTemplateId = null;
    public $projectId;

    #[Computed]
    public function availableTemplates() {
        return Template::whereNull('user_id')
            ->orWhere('user_id', Auth::id())
            ->get();
    }

    #[Computed]
    public function selectedTemplate() {
        if (!$this->selectedTemplateId) return null;
        return Template::with(['sections' => function($query) {
            $query->orderBy('order_index');
        }])->find($this->selectedTemplateId);
    }

    #[On('open-template-modal')]
    public function openModal($projectId) {
        $this->projectId = $projectId;
        $this->isOpen = true;
        $this->step = 1;
        $this->selectedTemplateId = null;
    }

    public function closeModal() {
        $this->isOpen = false;
        $this->step = 1;
        $this->selectedTemplateId = null;
    }

    public function viewDetail($templateId) {
        $this->selectedTemplateId = $templateId;
        $this->step = 2;
    }

    public function goBack() {
        $this->step = 1;
        $this->selectedTemplateId = null;
    }

    public function useTemplate() {
        $project = Project::find($this->projectId);
        
        // Jika belum pilih template
        if (!$project->template_id) {
            $this->confirmTemplateChange();
            return;
        }

        $oldTemplate = Template::with('sections')->find($project->template_id);
        $newTemplate = $this->selectedTemplate;

        $oldSections = $oldTemplate->sections->sortBy('order_index')->values();
        $newSections = $newTemplate->sections->sortBy('order_index')->values();

        $oldSectionCount = $oldSections->count();
        $newSectionCount = $newSections->count();

        if ($oldSectionCount > $newSectionCount) {
            
            // Ambil semua section lama yang indeksnya melebihi batas template baru (Index >= $newSectionCount)
            $excessOldSections = $oldSections->slice($newSectionCount)->pluck('structure_section_id');

            // cek apakah di excess section ada chapter
            $hasChaptersInExcessSections = ChapterCard::where('project_id', $this->projectId)
                ->whereIn('structure_section_id', $excessOldSections)
                ->exists();

            if ($hasChaptersInExcessSections) {
                $this->step = 3; 
                return;
            }
        }

        // Jika tidak ada chapter di excess section
        $this->confirmTemplateChange();
    }

    public function confirmTemplateChange() {
        $project = Project::find($this->projectId);
        $oldTemplateId = $project->template_id;
        
        if ($oldTemplateId) {
            $oldSections = Template::find($oldTemplateId)->sections()->orderBy('order_index')->get()->values();
            $newSections = $this->selectedTemplate->sections->sortBy('order_index')->values();
            $newSectionCount = $newSections->count();
            
            $lastNewSectionId = $newSections->last()->structure_section_id;

            // Iterasi setiap section lama untuk memindahkan chapternya
            foreach ($oldSections as $index => $oldSection) {
                $targetSectionId = ($index < $newSectionCount) 
                    ? $newSections[$index]->structure_section_id 
                    : $lastNewSectionId;

                // update section_id di semua chapter
                ChapterCard::where('project_id', $this->projectId)
                    ->where('structure_section_id', $oldSection->structure_section_id)
                    ->update(['structure_section_id' => $targetSectionId]);
            }
        }

        $project->update(['template_id' => $this->selectedTemplateId]);
        
        $this->dispatch('template-applied'); 
        $this->closeModal();
    }
}; ?>

<div>
    @if($isOpen)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-text-100/30 backdrop-blur-sm transition-opacity">
            
            <div class="relative bg-bg-main w-full max-w-3xl h-[90vh] rounded-2xl shadow-2xl flex flex-col overflow-hidden mx-4 border border-bg-border">
                
                <button wire:click="closeModal" class="absolute top-6 right-6 w-10 h-10 rounded-full border border-brand-200 text-text-60 hover:bg-brand-50 hover:text-secondary-200 flex items-center justify-center transition-colors z-50 bg-bg-main">
                    <x-icons.add-default rotate="45"/>
                </button>

                @if($step === 1)
                    <div class="shrink-0 pt-12 pb-6 px-10 text-center z-10 bg-bg-main">
                        <h2 class="text-web-heading-1 text-text-80 mb-2">Narrative Structures</h2>
                        <p class="text-app-subfeature text-text-60">
                            A lack of narrative structure, as you know, will cause anxiety.<br>- John Dufresne -
                        </p>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar px-10 pb-6 bg-bg-main">
                        <div class="flex flex-col gap-8 items-center w-full">
                            @foreach($this->availableTemplates as $template)
                                <div wire:click="viewDetail('{{ $template->template_id }}')" 
                                     class="group relative w-full max-w-lg aspect-[16/9] bg-[#212121] rounded-xl overflow-hidden cursor-pointer border border-transparent hover:border-brand-200 transition-all p-8 md:p-12 shadow-sm">
                                    
                                    <div class="w-full max-w-2xl h-full flex items-center justify-center transition-all duration-300 group-hover:blur-[1px] group-hover:opacity-90">
                                        @if($template->image_preview)
                                            <x-dynamic-component :component="$template->image_preview" class="w-full max-w-lg h-auto" />
                                        @else
                                            <div class="flex flex-col items-center justify-center h-48 text-brand-200">
                                                <x-icons.no-structure class="w-12 h-12 opacity-50 mb-2"/>
                                                <span class="text-app-desc-feature">No Preview Available</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="absolute bottom-8 left-0 bg-bg-main px-6 py-2.5 rounded-r-lg shadow-lg opacity-0 group-hover:opacity-100 transition-all transform -translate-x-2 group-hover:translate-x-0 duration-300">
                                        <span class="text-web-body-large text-text-60">{{ $template->name }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="shrink-0 p-6 flex justify-center bg-bg-main z-10 border-t border-bg-border/60">
                        <button class="flex items-center gap-2 px-6 py-2.5 bg-transparent border border-brand-200 text-secondary-200 rounded-md hover:bg-brand-50 hover:text-secondary-300 transition-colors text-web-button">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Create Template
                        </button>
                    </div>
                @endif

                @if($step === 2 && $this->selectedTemplate)
                    <div class="shrink-0 pt-6 px-6 relative z-10 bg-bg-main">
                        <button wire:click="goBack" class="w-10 h-10 rounded-full border border-brand-200 text-text-60 hover:bg-brand-50 hover:text-text-100 flex items-center justify-center transition-colors bg-bg-main" title="Back to structures">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar px-10 md:px-14 pb-14 bg-bg-main">
                        <div class="flex flex-col items-center max-w-4xl mx-auto w-full">
                            
                            <div class="text-center mb-10 w-full mt-4">
                                <h1 class="text-web-title text-text-100 mb-6">
                                    {{ $this->selectedTemplate->name }}
                                </h1>
                                <button wire:click="useTemplate" class="px-5 py-2 border border-brand-200 text-secondary-200 rounded-md hover:bg-brand-50 hover:text-secondary-300 transition-colors text-web-button">
                                    Use Template
                                </button>
                            </div>

                            <div class="w-full max-w-xl h-[60vh] bg-[#1C1C1C] rounded-xl p-8 flex items-center justify-center mb-8">
                                @if($this->selectedTemplate->image_preview)
                                    <x-dynamic-component :component="$this->selectedTemplate->image_preview" class="w-full h-auto max-w-lg" />
                                @endif
                            </div>
                            
                            <div class="w-full mb-12 px-3">
                                <hr class="mb-4 border-text-60 border-t-1">
                                <p class="text-web-body-small text-text-70 leading-relaxed whitespace-pre-wrap">{{ $this->selectedTemplate->description }}</p>
                                <hr class="mt-4 border-text-60 border-t-1">
                            </div>

                            <div class="w-full flex flex-col gap-10 px-3">
                                @forelse($this->selectedTemplate->sections as $section)
                                    <div>
                                        <h3 class="text-web-heading-1 text-text-90 mb-4">
                                            {{ $section->title }}
                                        </h3>
                                        <div class="prose prose-stone prose-p:text-text-70 prose-li:text-text-70 prose-strong:text-text-80 max-w-none text-web-body-small leading-relaxed">
                                            {!! Str::markdown($section->goal) !!}
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-center text-text-60 italic text-app-body-small">No detailed steps for this template yet.</p>
                                @endforelse
                            </div>

                        </div>
                    </div>
                @endif

                @if($step === 3)
                    <div class="flex-1 flex flex-col items-center justify-center p-10 text-center bg-bg-main h-full relative z-10 animate-in fade-in zoom-in-95 duration-300">
                        
                        <div class="w-35 h-35 bg-secondary-50/30 rounded-full flex items-center justify-center mb-6">
                            <x-icons.alert size="w-25 h-25" color="text-secondary-200"/>
                        </div>

                        <h3 class="text-app-title-2 text-text-90 mb-4">
                            Are you sure you want to change the template?
                        </h3>
                        
                        <p class="text-app-subfeature text-text-70 mb-10 max-w-2xl leading-relaxed">
                            Your current template has more sections than the new one you selected. All of your chapters in the excess sections will be merged into the <strong class="text-text-90 bg-brand-100 px-2 py-0.5 rounded">last section</strong> of your new template to prevent data loss.
                        </p>

                        <div class="flex gap-4">
                            <button wire:click="$set('step', 2)" class="px-8 py-3 rounded-md border-2 border-brand-200 text-text-70 hover:bg-brand-50 transition-colors font-semibold text-web-button shadow-sm">
                                Cancel
                            </button>
                            
                            <button wire:click="confirmTemplateChange" class="px-8 py-3 rounded-md bg-secondary-200 text-brand-10 hover:bg-secondary-300 hover:shadow-md transition-all font-semibold text-web-button shadow-sm transform active:scale-95">
                                Yes, Change Template
                            </button>
                        </div>
                    </div>
                @endif
                
            </div>
        </div>
    @endif
</div>