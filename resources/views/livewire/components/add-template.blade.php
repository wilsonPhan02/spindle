<?php
use Livewire\Volt\Component;
use App\Models\Template;
use App\Models\Project;
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
        Project::find($this->projectId)->update(['template_id' => $this->selectedTemplateId]);
        $this->dispatch('template-applied'); 
        $this->closeModal();
    }
}; ?>

<div>
    @if($isOpen)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-text-100/30 backdrop-blur-sm transition-opacity">
            
            <div class="relative bg-bg-main w-full max-w-4xl h-[90vh] rounded-2xl shadow-2xl flex flex-col overflow-hidden mx-4 border border-bg-border">
                
                <button wire:click="closeModal" class="absolute top-6 right-6 w-10 h-10 rounded-full border border-brand-200 text-text-60 hover:bg-brand-50 hover:text-text-100 flex items-center justify-center transition-colors z-50 bg-bg-main">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                @if($step === 1)
                    <div class="shrink-0 pt-12 pb-6 px-10 text-center z-10 bg-bg-main">
                        <h2 class="text-web-heading-1 text-text-80 mb-2">Narrative Structures</h2>
                        <p class="text-app-body-medium text-secondary-200 italic">
                            A lack of narrative structure, as you know, will cause anxiety.<br>
                            <span class="text-app-body-small opacity-80 text-text-60 not-italic">- John Dufresne -</span>
                        </p>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar px-10 pb-6 bg-bg-main">
                        <div class="flex flex-col gap-8 items-center w-full">
                            @foreach($this->availableTemplates as $template)
                                <div wire:click="viewDetail('{{ $template->template_id }}')" 
                                     class="group relative w-full max-w-2xl h-auto bg-[#1C1C1C] rounded-xl overflow-hidden cursor-pointer border border-transparent hover:border-brand-200 transition-all p-8 md:p-12 shadow-sm">
                                    
                                    <div class="w-full flex items-center justify-center transition-all duration-300 group-hover:blur-[4px] group-hover:opacity-70">
                                        @if($template->image_preview)
                                            <x-dynamic-component :component="$template->image_preview" class="w-full max-w-lg h-auto" />
                                        @else
                                            <div class="flex flex-col items-center justify-center h-48 text-brand-200">
                                                <x-icons.no-structure class="w-12 h-12 opacity-50 mb-2"/>
                                                <span class="text-app-body-small">No Preview Available</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="absolute bottom-8 left-0 bg-bg-main px-6 py-2.5 rounded-r-lg shadow-lg opacity-0 group-hover:opacity-100 transition-all transform -translate-x-2 group-hover:translate-x-0 duration-300">
                                        <span class="text-app-subheading-2 text-text-80">{{ $template->name }}</span>
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
                                <h1 class="text-web-title text-text-90 mb-6">
                                    {{ $this->selectedTemplate->name }}
                                </h1>
                                <button wire:click="useTemplate" class="px-5 py-2 border border-brand-200 text-secondary-200 rounded-md hover:bg-brand-50 hover:text-secondary-300 transition-colors text-web-button">
                                    Use Template
                                </button>
                            </div>

                            <div class="w-full bg-[#1C1C1C] rounded-xl p-8 flex items-center justify-center mb-8">
                                @if($this->selectedTemplate->image_preview)
                                    <x-dynamic-component :component="$this->selectedTemplate->image_preview" class="w-full h-auto max-w-2xl" />
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
                                        <h3 class="text-app-heading-1 font-merriweather text-text-90 mb-4">
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
                
            </div>
        </div>
    @endif
</div>