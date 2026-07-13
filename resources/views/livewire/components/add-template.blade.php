<?php
use Livewire\Volt\Component;
use App\Models\Template;
use App\Models\Project;
use App\Models\ChapterCard;
use App\Models\StructureSection;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public bool $isOpen = false;
    public int $step = 1; 
    public ?string $selectedTemplateId = null;
    public $projectId;

    public ?string $editingTemplateId = null; 
    public string $customTemplateName = '';
    public string $customTemplateDescription = '';
    public $customImagePreview;
    public ?string $existingImagePath = null; 
    public array $customSections = [];

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

    public function goToStep4() {
        $this->resetCustomForm();
        $this->step = 4;
        $this->addCustomSection();
    }

    public function editCustomTemplate($templateId) {
        $template = Template::with('sections')->find($templateId);
        
        if (!$template || !$template->is_custom) return;

        $this->editingTemplateId = $template->template_id;
        $this->customTemplateName = $template->name;
        $this->customTemplateDescription = $template->description;
        $this->existingImagePath = $template->image_preview;
        $this->customImagePreview = null;

        // Load section yang sudah ada
        $this->customSections = $template->sections->sortBy('order_index')->map(function($sec) {
            return [
                'id' => $sec->structure_section_id,
                'title' => $sec->title,
                'goal' => $sec->goal
            ];
        })->toArray();

        $this->step = 4;
    }

    public function addCustomSection() {
        $newIndex = count($this->customSections) + 1;
        $this->customSections[] = [
            'id' => null, // null berarti ini section baru
            'title' => __('Section') . ' ' . $newIndex, 
            'goal' => ''
        ];
    }

    public function removeCustomSection($index) {
        unset($this->customSections[$index]);
        $this->customSections = array_values($this->customSections); 
    }

    public function resetCustomForm() {
        $this->editingTemplateId = null;
        $this->customTemplateName = '';
        $this->customTemplateDescription = '';
        $this->customImagePreview = null;
        $this->existingImagePath = null;
        $this->customSections = [];
    }

    public function removeImage() {
        $this->customImagePreview = null;
        $this->existingImagePath = null;
    }

    public function saveCustomTemplate() {
        $rules = [
            'customTemplateName' => 'required|string|max:100',
            'customTemplateDescription' => 'nullable|string',
            'customImagePreview' => 'nullable|image|max:5120',
            'customSections' => 'required|array|min:1',
            'customSections.*.title' => 'required|string|max:40',
            'customSections.*.goal' => 'nullable|string',
        ];

        $messages = [
            'customTemplateName.required' => __('The structure name is required.'),
            'customTemplateName.max' => __('The structure name must not be greater than 100 characters.'),
            'customImagePreview.image' => __('The cover image must be a valid image file.'),
            'customImagePreview.max' => __('The cover image size must not exceed 5MB.'),
            'customSections.required' => __('At least one narrative section is required.'),
            'customSections.min' => __('At least one narrative section is required.'),
            'customSections.*.title.required' => __('The section title is required.'),
            'customSections.*.title.max' => __('The section title must not be greater than 40 characters.'),
        ];

        $attributes = [
            'customTemplateName' => __('structure name'),
            'customTemplateDescription' => __('description'),
            'customImagePreview' => __('cover image'),
            'customSections' => __('narrative sections'),
        ];

        foreach ($this->customSections as $index => $section) {
            $num = $index + 1;
            $attributes["customSections.{$index}.title"] = __("section :num title", ['num' => $num]);
            $attributes["customSections.{$index}.goal"] = __("section :num description", ['num' => $num]);
        }

        try {
            $this->validate($rules, $messages, $attributes);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->setErrorBag($e->validator->errors());
            $this->dispatch('scroll-to-first-error');
            return;
        }

        DB::transaction(function () {
            // Upload gambar baru jika ada
            $imagePath = $this->existingImagePath;
            if ($this->customImagePreview) {
                // Hapus gambar lama jika ada
                if ($this->existingImagePath) Storage::disk('public')->delete($this->existingImagePath);
                $imagePath = $this->customImagePreview->store('templates', 'public');
            }

            if ($this->editingTemplateId) {
                $template = Template::find($this->editingTemplateId);

                $oldImagePath = $template->image_preview;

                $imagePath = $this->existingImagePath;
                if ($this->customImagePreview) {
                    if ($oldImagePath) Storage::disk('public')->delete($oldImagePath);
                    $imagePath = $this->customImagePreview->store('templates', 'public');
                } else if (is_null($this->existingImagePath) && $oldImagePath) {
                    // Jika gambar lama di-detach, hapus file aslinya dari server
                    Storage::disk('public')->delete($oldImagePath);
                }

                $template->update([
                    'name' => $this->customTemplateName,
                    'description' => $this->customTemplateDescription,
                    'image_preview' => $imagePath,
                ]);

                $keptSectionIds = collect($this->customSections)->pluck('id')->filter()->toArray();
                $sectionsToDelete = StructureSection::where('template_id', $template->template_id)
                    ->whereNotIn('structure_section_id', $keptSectionIds)
                    ->pluck('structure_section_id');

                $lastValidSectionId = null;

                foreach ($this->customSections as $index => $section) {
                    if (!empty($section['id'])) {
                        StructureSection::where('structure_section_id', $section['id'])
                            ->update(['order_index' => $index + 1, 'title' => $section['title'], 'goal' => $section['goal']]);
                        $lastValidSectionId = $section['id'];
                    } else {
                        $newSec = StructureSection::create([
                            'template_id' => $template->template_id,
                            'order_index' => $index + 1,
                            'title' => $section['title'],
                            'goal' => $section['goal']
                        ]);
                        $lastValidSectionId = $newSec->structure_section_id;
                    }
                }

                if ($sectionsToDelete->isNotEmpty() && $lastValidSectionId) {
                    ChapterCard::whereIn('structure_section_id', $sectionsToDelete)
                        ->update(['structure_section_id' => $lastValidSectionId]);
                        
                    StructureSection::whereIn('structure_section_id', $sectionsToDelete)->delete();
                }

                $this->selectedTemplateId = $template->template_id;

            } else {
                // CREATE TEMPLATE BARU
                $template = Template::create([
                    'user_id' => Auth::id(),
                    'name' => $this->customTemplateName,
                    'description' => $this->customTemplateDescription,
                    'image_preview' => $imagePath,
                    'is_custom' => true,
                ]);

                foreach ($this->customSections as $index => $section) {
                    StructureSection::create([
                        'template_id' => $template->template_id, 
                        'order_index' => $index + 1,
                        'title' => $section['title'],
                        'goal' => $section['goal'],
                    ]);
                }
                $this->selectedTemplateId = $template->template_id;
            }
        });

        $project = Project::find($this->projectId);
        if ($project && $project->template_id == $this->selectedTemplateId) {
            $this->dispatch('template-applied');
        }

        $this->step = 2; 
        $this->resetCustomForm();
    }

    // --- FITUR DELETE CUSTOM TEMPLATE --- //

    public function attemptDeleteTemplate($templateId) {
        $inUse = Project::where('template_id', $templateId)->exists();
        
        if ($inUse) {
            // Panggil modal peringatan
            $this->dispatch('show-template-in-use-warning');
        } else {
            // Panggil modal delete dan kirim ID-nya ke Alpine
            $this->dispatch('open-delete-template-dialog', id: $templateId);
        }
    }

    public function confirmDeleteTemplate($templateId) {
        DB::transaction(function () use ($templateId) {
            $template = Template::find($templateId);
            if ($template && $template->image_preview) {
                Storage::disk('public')->delete($template->image_preview);
            }
            StructureSection::where('template_id', $templateId)->delete();
            $template?->delete();
        });
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
    {{-- Top loading progress bar (appears at top of browser page matching global NProgress bar style) --}}
    <div wire:loading class="fixed top-0 inset-x-0 z-[200] h-[2px] overflow-hidden pointer-events-none">
        <div class="h-full animate-progress-fill"></div>
    </div>

    @if($isOpen)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 backdrop-blur-sm transition-opacity">
            
            <div class="relative bg-bg-main w-full max-w-3xl h-[90vh] rounded-2xl shadow-2xl flex flex-col overflow-hidden mx-4 border border-bg-border">
                
                <button wire:click="closeModal" wire:loading.attr="disabled" class="absolute top-6 right-6 w-10 h-10 rounded-full border border-brand-200 text-text-60 hover:bg-brand-50 hover:text-secondary-200 flex items-center justify-center transition-colors z-50 bg-bg-main disabled:opacity-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                @if($step === 1)
                    <div class="shrink-0 pt-12 pb-6 px-10 text-center z-10 bg-bg-main">
                        <h2 class="text-web-heading-1 text-text-80 mb-2">{{ __('Narrative Structures') }}</h2>
                        <p class="text-app-subfeature text-text-60">
                            {!! __('A lack of narrative structure, as you know, will cause anxiety.<br>- John Dufresne -') !!}
                        </p>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar px-10 pb-6 bg-bg-main">
                        <div class="flex flex-col gap-8 items-center w-full">
                            @foreach($this->availableTemplates as $template)
                                <div wire:click="viewDetail('{{ $template->template_id }}')" 
                                     wire:loading.class="opacity-60 pointer-events-none"
                                     class="group relative w-full max-w-lg aspect-[16/9] bg-[#212121] rounded-xl overflow-hidden cursor-pointer border border-transparent hover:border-brand-200 transition-all p-8 md:p-12 shadow-sm">
                                    
                                    @if($template->image_preview && $template->is_custom)
                                        <img src="{{ Storage::url($template->image_preview) }}" alt="{{ __('Preview') }}" class="absolute inset-0 w-full h-full object-cover opacity-80 group-hover:opacity-100 group-hover:blur-[1px] transition-all duration-300 z-0" />
                                    @endif

                                    <div class="relative z-10 w-full max-w-2xl h-full flex items-center justify-center transition-all duration-300 group-hover:blur-[1px] group-hover:opacity-90 pointer-events-none">
                                        @if($template->image_preview)
                                            @if(!$template->is_custom)
                                                <img src="{{ asset('images/' . str_replace('.', '/', $template->image_preview) . '.svg') }}" class="w-full max-w-lg h-auto" alt="{{ __('Template Preview') }}" />
                                            @endif
                                        @else
                                            <div class="flex flex-col items-center justify-center h-48 text-brand-200">
                                                <x-icons.no-structure class="w-12 h-12 opacity-50 mb-2"/>
                                                <span class="text-app-desc-feature">{{ __('No Preview Available') }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="absolute z-20 bottom-8 left-0 bg-bg-main px-6 py-2.5 rounded-r-lg shadow-lg opacity-0 group-hover:opacity-100 transition-all transform -translate-x-2 group-hover:translate-x-0 duration-300 max-w-sm">
                                        <span class="block truncate w-full text-web-body-large text-text-60" title="{{ $template->name }}">
                                            {{ $template->name }}
                                        </span>
                                    </div>

                                    @if($template->is_custom)
                                        <div class="absolute top-3 right-3 flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-all transform translate-y-2 group-hover:translate-y-0 duration-300">
                                            
                                            <button @click.stop wire:click="editCustomTemplate('{{ $template->template_id }}')" 
                                                    wire:loading.attr="disabled"
                                                    class="flex items-center gap-1 px-2 py-1 bg-bg-main/95 backdrop-blur-sm border border-brand-200 text-secondary-200 rounded-sm hover:bg-brand-200 transition-all shadow-md disabled:opacity-50" 
                                                    title="{{ __('Edit Structure') }}">
                                                <x-icons.rename class="w-3 h-3"/>
                                                <span class="text-app-caption font-semibold">{{ __('Edit') }}</span>
                                            </button>
                                            
                                            <button @click.stop wire:click="attemptDeleteTemplate('{{ $template->template_id }}')" 
                                                    wire:loading.attr="disabled"
                                                    class="flex items-center gap-2 px-2 py-1 bg-bg-main/95 backdrop-blur-sm border border-danger-100/50 text-danger-100 rounded-sm hover:bg-danger-100 hover:border-danger-100 hover:text-brand-50 transition-all shadow-md group/btn disabled:opacity-50" 
                                                    title="{{ __('Delete Structure') }}">
                                                <x-icons.delete class="w-3 h-3" />
                                                <span class="text-app-caption font-semibold">{{ __('Delete') }}</span>
                                            </button>
                                            
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="shrink-0 p-6 flex justify-center bg-bg-main z-10 border-t border-bg-border/60">
                        <button wire:click="goToStep4" wire:loading.attr="disabled" class="flex items-center gap-2 px-6 py-2.5 bg-transparent border border-brand-200 text-secondary-200 rounded-md hover:bg-brand-50 hover:text-secondary-300 transition-colors text-web-button disabled:opacity-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            {{ __('Create Template') }}
                        </button>
                    </div>
                @endif

                @if($step === 2 && $this->selectedTemplate)
                    <div x-data="{ showStickyBtn: false }" class="flex flex-col flex-1 overflow-hidden">

                        {{-- Header bar with back + conditional sticky Use Template button --}}
                        <div class="shrink-0 pt-6 px-6 pb-4 relative z-10 bg-bg-main flex items-center justify-between">
                            <button wire:click="goBack" wire:loading.attr="disabled" class="w-10 h-10 rounded-full border border-brand-200 text-text-60 hover:bg-brand-50 hover:text-text-100 flex items-center justify-center transition-colors bg-bg-main disabled:opacity-50" title="{{ __('Back to structures') }}">
                                <x-icons.back/>
                            </button>

                            {{-- Sticky Use Template button — only visible when primary button is out of view --}}
                            <div
                                x-show="showStickyBtn"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="mr-14 sm:mr-16"
                                style="display: none;"
                            >
                                <button wire:click="useTemplate" wire:loading.attr="disabled" class="h-10 min-w-[145px] px-5 border border-brand-200 text-secondary-200 rounded-md hover:bg-brand-50 hover:text-secondary-300 transition-colors text-web-button flex items-center justify-center gap-2 shadow-sm disabled:opacity-60 disabled:cursor-not-allowed">
                                    <span wire:loading.remove wire:target="useTemplate">{{ __('Use Template') }}</span>
                                    <span wire:loading.flex wire:target="useTemplate" class="items-center justify-center gap-2">
                                        <svg class="animate-spin h-3.5 w-3.5 shrink-0 text-secondary-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        <span>{{ __('Applying...') }}</span>
                                    </span>
                                </button>
                            </div>
                        </div>

                        {{-- Scrollable content --}}
                        <div class="flex-1 overflow-y-auto custom-scrollbar px-10 md:px-14 pb-14 bg-bg-main">
                            <div class="flex flex-col items-center max-w-4xl mx-auto w-full">

                                <div class="text-center mb-10 w-full mt-4 max-w-xl mx-auto">
                                    <h1 class="block w-full text-app-title-1 text-text-100 mb-6 break-words" title="{{ $this->selectedTemplate->name }}">
                                        {{ $this->selectedTemplate->name }}
                                    </h1>

                                    {{-- Primary Use Template button — observed by IntersectionObserver --}}
                                    <div
                                        x-ref="primaryUseBtn"
                                        x-init="() => {
                                            const observer = new IntersectionObserver(([entry]) => {
                                                showStickyBtn = !entry.isIntersecting;
                                            }, { threshold: 0.5 });
                                            observer.observe($refs.primaryUseBtn);
                                        }"
                                    >
                                        <button wire:click="useTemplate" wire:loading.attr="disabled" class="px-5 py-2 min-w-[160px] border border-brand-200 text-secondary-200 rounded-md hover:bg-brand-50 hover:text-secondary-300 transition-colors text-web-button flex items-center justify-center gap-2 mx-auto disabled:opacity-60 disabled:cursor-not-allowed">
                                            <span wire:loading.remove wire:target="useTemplate">{{ __('Use Template') }}</span>
                                            <span wire:loading.flex wire:target="useTemplate" class="items-center justify-center gap-2">
                                                <svg class="animate-spin h-4 w-4 shrink-0 text-secondary-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                <span>{{ __('Applying...') }}</span>
                                            </span>
                                        </button>
                                    </div>
                                </div>

                                <div class="relative overflow-hidden w-full max-w-xl h-[60vh] bg-[#212121] rounded-xl p-8 flex items-center justify-center mb-8">
                                    @if($this->selectedTemplate->image_preview)
                                        @if($this->selectedTemplate->is_custom)
                                            <img src="{{ Storage::url($this->selectedTemplate->image_preview) }}" alt="{{ __('Preview') }}" class="absolute inset-0 w-full h-full object-cover" />
                                        @else
                                            <img src="{{ asset('images/' . str_replace('.', '/', $this->selectedTemplate->image_preview) . '.svg') }}" class="w-full h-auto max-w-lg" alt="{{ __('Template Preview') }}" />
                                        @endif
                                    @else
                                        <div class="flex flex-col items-center justify-center h-48 text-brand-200">
                                            <x-icons.no-structure class="w-12 h-12 opacity-50 mb-2"/>
                                            <span class="text-app-desc-feature">{{ __('No Preview Available') }}</span>
                                        </div>
                                    @endif
                                </div>


                                <div class="w-full mb-12 px-3">
                                    <hr class="mb-4 border-text-60 border-t-1">
                                    @if ($this->selectedTemplate->description)
                                        <p class="text-web-body-small text-text-70 leading-relaxed whitespace-pre-wrap">{{ $this->selectedTemplate->description }}</p>
                                    @else
                                        <p class="text-center text-web-body-small text-text-70 leading-relaxed whitespace-pre-wrap">{{ __('No description for this template') }}</p>
                                    @endif
                                    <hr class="mt-4 border-text-60 border-t-1">
                                </div>


                                <div class="w-full flex flex-col gap-10 px-3">
                                    @forelse($this->selectedTemplate->sections as $section)
                                        <div>
                                            <h3 class="text-app-title-1 font-semibold text-text-90 mb-4">
                                                {{ $section->title }}
                                            </h3>
                                            @if ($section->goal)
                                                <div class="prose prose-stone prose-p:text-text-70 prose-li:text-text-70 prose-strong:text-text-80 max-w-none text-web-body-small leading-relaxed">
                                                    {!! Str::markdown($section->goal) !!}
                                                </div>
                                            @else
                                                <p class=" text-text-60 italic text-app-body-small">{{ __('No details for this section yet.') }}</p>
                                            @endif
                                        </div>
                                    @empty
                                        <p class="text-center text-text-60 italic text-app-body-small">{{ __('No detailed steps for this template yet.') }}</p>
                                    @endforelse
                                </div>

                            </div>
                        </div>
                    </div>
                @endif

                @if($step === 3)
                    <div class="flex-1 flex flex-col items-center justify-center p-10 text-center bg-bg-main h-full relative z-10 animate-in fade-in zoom-in-95 duration-300">
                        
                        <div class="w-35 h-35 bg-secondary-50/30 rounded-full flex items-center justify-center mb-6">
                            <x-icons.alert size="w-25 h-25" color="text-secondary-200"/>
                        </div>

                        <h3 class="text-app-heading-1 text-text-90 mb-4">
                            {{ __('Are you sure you want to change the template?') }}
                        </h3>
                        
                        <p class="text-app-subfeature text-text-70 mb-10 max-w-2xl leading-relaxed">
                            {!! __('Your current template has more sections than the new one you selected. All of your chapters in the excess sections will be merged into the <strong class="text-text-90 bg-brand-100 px-2 py-0.5 rounded">last section</strong> of your new template to prevent data loss.') !!}
                        </p>

                        <div class="flex gap-4">
                            <button wire:click="$set('step', 2)" wire:loading.attr="disabled" class="px-8 py-3 rounded-md border-2 border-brand-200 text-text-70 hover:bg-brand-50 transition-colors font-semibold text-web-button shadow-sm disabled:opacity-50">
                                {{ __('Cancel') }}
                            </button>
                            
                            <button wire:click="confirmTemplateChange" wire:loading.attr="disabled" class="px-8 py-3 min-w-[230px] rounded-md bg-secondary-200 text-brand-10 hover:bg-secondary-300 hover:shadow-md transition-all font-semibold text-web-button shadow-sm transform active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                                <span wire:loading.remove wire:target="confirmTemplateChange">{{ __('Yes, Change Template') }}</span>
                                <span wire:loading.flex wire:target="confirmTemplateChange" class="items-center justify-center gap-2.5">
                                    <svg class="animate-spin h-4 w-4 shrink-0 text-brand-10" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span>{{ __('Applying...') }}</span>
                                </span>
                            </button>
                        </div>
                    </div>
                @endif
                
                @if($step === 4)
                    <div class="shrink-0 pt-6 px-6 relative z-10 bg-bg-main flex items-center border-b border-bg-border/60 pb-4">
                        <button wire:click="goBack" wire:loading.attr="disabled" class="w-10 h-10 rounded-full border border-brand-200 text-text-60 hover:bg-brand-50 hover:text-text-100 flex items-center justify-center transition-colors bg-bg-main disabled:opacity-50" title="{{ __('Back to structures') }}">
                            <x-icons.back/>
                        </button>
                        <h2 class="text-app-title-1 text-text-90 ml-6">{{ __('Architect Your Structure') }}</h2>
                    </div>

                    <div
                        x-ref="formScrollContainer"
                        x-on:scroll-to-first-error.window="() => {
                            try {
                                $nextTick(() => {
                                    const firstError = $el.querySelector('[data-validation-error=\'true\']');
                                    if (firstError) {
                                        const targetElement = firstError.closest('.form-field-group') || firstError.closest('div') || firstError;
                                        targetElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                        const input = targetElement.querySelector('input, textarea, select');
                                        if (input && typeof input.focus === 'function') {
                                            setTimeout(() => input.focus({ preventScroll: true }), 350);
                                        }
                                    }
                                });
                            } catch (e) {}
                        }"
                        class="flex-1 overflow-y-auto custom-scrollbar px-10 md:px-14 py-8 bg-bg-main"
                    >
                        <form wire:submit.prevent="saveCustomTemplate" class="max-w-3xl mx-auto flex flex-col gap-8 pb-10">
                            
                            <div class="flex flex-col gap-4">
                                <div class="form-field-group">
                                    <label class="block text-app-desc-feature font-bold text-text-70 uppercase tracking-wider mb-2">{{ __('Template Name') }} <span class="text-danger-100">*</span></label>
                                    <input type="text" wire:model="customTemplateName" placeholder="{{ __('e.g. My Hero\'s Journey') }}" class="w-full bg-card-bg border @error('customTemplateName') border-danger-100 ring-1 ring-danger-100 @else border-card-border @enderror rounded-lg px-4 py-3 text-text-90 placeholder-text-60/40 focus:outline-none focus:border-secondary-200 focus:ring-1 focus:ring-secondary-200 transition-all text-app-body-medium">
                                    @error('customTemplateName') <span data-validation-error="true" class="text-danger-100 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                
                                <div class="form-field-group">
                                    <label class="block text-app-desc-feature font-bold text-text-70 uppercase tracking-wider mb-2">{{ __('Description') }}</label>
                                    <textarea wire:model="customTemplateDescription" rows="2" placeholder="{{ __('Briefly explain what this structure is for...') }}" class="w-full bg-card-bg border @error('customTemplateDescription') border-danger-100 ring-1 ring-danger-100 @else border-card-border @enderror rounded-lg px-4 py-3 text-text-90 placeholder-text-60/40 focus:outline-none focus:border-secondary-200 focus:ring-1 focus:ring-secondary-200 transition-all custom-scrollbar resize-none text-app-body-medium"></textarea>
                                    @error('customTemplateDescription') <span data-validation-error="true" class="text-danger-100 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div x-data="{ 
                                        hoverCover: false,
                                        isUploading: false, 
                                        progress: 0, 
                                        clientError: null,
                                        showCropper: false,
                                        cropImageUrl: null,
                                        cropperInstance: null,
                                        
                                        cancelCrop() {
                                            this.showCropper = false;
                                            if (this.cropperInstance) {
                                                this.cropperInstance.destroy();
                                                this.cropperInstance = null;
                                            }
                                            this.cropImageUrl = null;
                                            if(this.$refs.coverInput) this.$refs.coverInput.value = null;
                                        },
                                        
                                        onFileChange(e) {
                                            const file = e.target.files[0];
                                            if (!file) return;
                                            
                                            if (file.size > 5 * 1024 * 1024) {
                                                this.clientError = '{{ __('The selected image is too large. The maximum allowed file size is 5MB.') }}';
                                                this.$refs.coverInput.value = '';
                                                return;
                                            }
                                            
                                            this.clientError = null;
                                            
                                            const reader = new FileReader();
                                            reader.onload = (event) => {
                                                this.cropImageUrl = event.target.result;
                                                this.showCropper = true;
                                                
                                                this.$nextTick(() => {
                                                    const image = this.$refs.cropImage;
                                                    this.cropperInstance = new Cropper(image, {
                                                        aspectRatio: 16 / 9,
                                                        viewMode: 3,
                                                        autoCropArea: 1,
                                                        dragMode: 'move',
                                                        background: false,
                                                        guides: false,
                                                        center: true,
                                                        highlight: false,
                                                        cropBoxMovable: false,
                                                        cropBoxResizable: false,
                                                    });
                                                });
                                            };
                                            reader.readAsDataURL(file);
                                        },
                                        
                                        applyCrop() {
                                            if (!this.cropperInstance) return;
                                            
                                            const canvas = this.cropperInstance.getCroppedCanvas({
                                                width: 1280,
                                                height: 720,
                                                imageSmoothingEnabled: true,
                                                imageSmoothingQuality: 'high',
                                            });
                                            
                                            canvas.toBlob((blob) => {
                                                const file = new File([blob], 'template-cover.jpg', { type: 'image/jpeg', lastModified: Date.now() });
                                                
                                                this.cancelCrop();
                                                
                                                this.isUploading = true;
                                                this.progress = 0;
                                                
                                                @this.upload('customImagePreview', file,
                                                    (uploadedFilename) => { this.isUploading = false; },
                                                    () => { this.isUploading = false; },
                                                    (e) => { this.progress = e.detail.progress; }
                                                );
                                            }, 'image/jpeg', 0.9);
                                        }
                                    }" class="form-field-group">
                                    <label class="block text-app-desc-feature font-bold text-text-70 uppercase tracking-wider mb-2">{{ __('Cover Image') }}</label>
                                    
                                    <div @mouseover="hoverCover = true" @mouseleave="hoverCover = false" class="relative w-full max-w-lg aspect-[16/9] mx-auto border border-brand-100 rounded-lg bg-card-bg transition-colors flex flex-col items-center justify-center overflow-hidden">
                                        
                                        <!-- Progress Overlay -->
                                        <div x-show="isUploading" x-transition class="absolute inset-0 bg-secondary-5/80 backdrop-blur-md z-40 flex flex-col items-center justify-center">
                                            <svg class="animate-spin h-8 w-8 text-secondary-200 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <div class="text-secondary-200 font-semibold text-sm">{{ __('Uploading...') }} <span x-text="progress + '%'"></span></div>
                                            
                                            <div class="w-3/4 bg-brand-150 rounded-full h-1.5 mt-3 overflow-hidden shadow-inner mx-auto">
                                                <div class="bg-secondary-100 h-full rounded-full transition-all duration-200 ease-out" :style="`width: ${progress}%`"></div>
                                            </div>
                                        </div>

                                        @if ($customImagePreview || $existingImagePath)
                                            @if ($customImagePreview)
                                                <img src="{{ $customImagePreview->temporaryUrl() }}" class="absolute inset-0 w-full h-full object-cover opacity-100 z-10">
                                            @elseif ($existingImagePath)
                                                <img src="{{ Storage::url($existingImagePath) }}" class="absolute inset-0 w-full h-full object-cover opacity-100 z-10">
                                            @endif
                                        @else
                                            <div class="absolute inset-0 z-10 flex flex-col items-center justify-center pointer-events-none opacity-50">
                                                <svg class="w-8 h-8 text-text-40 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                <span class="text-app-body-small text-text-60">{{ __('Upload Cover Image') }}</span>
                                            </div>
                                        @endif

                                        <div x-show="hoverCover && !showCropper" x-transition class="absolute bottom-5 left-5 z-30 flex gap-2">
                                            <label class="flex items-center gap-2 px-3.5 py-2 bg-black/75 backdrop-blur-md border border-white/15 rounded-lg cursor-pointer hover:bg-black/90 transition-all shadow-xl text-white">
                                                <x-icons.upload class="w-4 h-4 text-white" />
                                                <span class="text-white font-semibold text-app-desc-feature">{{ $customImagePreview || $existingImagePath ? __('Change Cover') : __('Upload Cover') }}</span>
                                                <input type="file" x-ref="coverInput" @change="onFileChange" accept="image/*" class="hidden">
                                            </label>

                                            @if ($customImagePreview || $existingImagePath)
                                                <button type="button" wire:click.prevent="removeImage" class="flex items-center gap-2 px-3.5 py-2 bg-black/75 backdrop-blur-md border border-danger-100/40 rounded-lg cursor-pointer hover:bg-danger-100/20 transition-all shadow-xl text-danger-100">
                                                    <x-icons.delete class="w-4 h-4 text-danger-100" />
                                                    <span class="text-app-desc-feature font-semibold text-danger-100">{{ __('Remove') }}</span>
                                                </button>
                                            @endif
                                        </div>

                                        {{-- Client-side Error --}}
                                        <template x-if="clientError">
                                            <div class="absolute inset-x-4 top-4 bg-danger-100/95 text-bg-main text-[12px] font-medium px-3 py-2.5 rounded shadow-xl z-50 flex items-start gap-2">
                                                <svg class="w-4 h-4 mt-0.5 shrink-0 text-bg-main" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                <span x-text="clientError" class="flex-1 leading-relaxed"></span>
                                                <button type="button" @click.stop="clientError = null" class="shrink-0 ml-2 p-0.5 hover:bg-black/20 rounded transition-colors" title="{{ __('Dismiss') }}"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                                            </div> 
                                        </template>

                                        {{-- Server-side Error --}}
                                        @error('customImagePreview') 
                                            <div data-validation-error="true" x-data="{ show: true }" x-show="show" class="absolute inset-x-4 top-4 bg-danger-100/95 text-bg-main text-[12px] font-medium px-3 py-2.5 rounded shadow-xl z-50 flex items-start gap-2">
                                                <svg class="w-4 h-4 mt-0.5 shrink-0 text-bg-main" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                <span class="flex-1 leading-relaxed">{{ $message }}</span>
                                                <button type="button" @click.stop="show = false" class="shrink-0 ml-2 p-0.5 hover:bg-black/20 rounded transition-colors" title="{{ __('Dismiss') }}"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                                            </div> 
                                        @enderror

                                        {{-- Inline Cropper UI --}}
                                        <div x-show="showCropper" style="display: none;" class="absolute inset-0 z-40 bg-brand-50 flex flex-col">
                                            <div class="absolute inset-0 w-full h-full bg-black overflow-hidden">
                                                <img x-ref="cropImage" :src="cropImageUrl" class="block w-full h-full" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                            </div>
                                            <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-2 z-50">
                                                <button @click.stop="cancelCrop()" type="button" class="px-4 py-1.5 bg-bg-main/90 backdrop-blur text-text-70 text-[11px] font-bold uppercase tracking-wider rounded-md border border-text-60 hover:bg-bg-main shadow-lg transition-colors">{{ __('Cancel') }}</button>
                                                <button @click.stop="applyCrop()" type="button" class="px-4 py-1.5 bg-secondary-100/95 backdrop-blur text-bg-main text-[11px] font-bold uppercase tracking-wider rounded-md shadow-lg border border-secondary-200 hover:bg-secondary-200 transition-colors">{{ __('Save') }}</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="border-t border-bg-border/60">

                            <div class="flex flex-col gap-5">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-app-heading-1 text-text-90">{{ __('Narrative Sections') }}</h3>
                                </div>

                                @foreach($customSections as $index => $section)
                                    <div class="form-field-group bg-card-bg border border-card-border rounded-xl p-5 shadow-sm relative group" wire:key="section-{{ $index }}">
                                        
                                        <div class="flex gap-4 items-start mb-4">
                                            <div class="w-8 h-8 rounded bg-brand-100 text-secondary-200 flex items-center justify-center font-bold text-app-body-small shrink-0 mt-1">
                                                {{ $index + 1 }}
                                            </div>
                                            <div class="flex-1">
                                                <input type="text" wire:model="customSections.{{ $index }}.title" placeholder="{{ __('e.g. Inciting Incident') }}" class="w-full bg-transparent border-b @error('customSections.'.$index.'.title') border-danger-100 @else border-card-border @enderror focus:border-secondary-200 px-1 py-1.5 text-text-90 placeholder-text-60/40 focus:outline-none transition-all text-app-body-large">
                                                @error('customSections.'.$index.'.title') <span data-validation-error="true" class="text-danger-100 text-xs mt-1 block">{{ $message }}</span> @enderror
                                            </div>
                                            
                                            @if(count($customSections) > 1)
                                                <button type="button" wire:click="removeCustomSection({{ $index }})" class="text-text-60/70 hover:text-danger-100 p-1.5 pt-0 rounded-md hover:bg-danger-100/10 transition-colors mt-1" title="{{ __('Remove section') }}">
                                                    <x-icons.delete class="w-4 h-4" />
                                                </button>
                                            @endif
                                        </div>

                                        <div class="pl-12">
                                            <textarea wire:model="customSections.{{ $index }}.goal" rows="2" placeholder="{{ __('Describe what needs to happen in this section...') }}" class="w-full bg-bg-main border @error('customSections.'.$index.'.goal') border-danger-100 ring-1 ring-danger-100 @else border-card-border @enderror rounded-lg px-3 py-2 text-text-80 placeholder-text-60/40 focus:outline-none focus:border-secondary-200 transition-all custom-scrollbar resize-none text-app-body-small"></textarea>
                                            @error('customSections.'.$index.'.goal') <span data-validation-error="true" class="text-danger-100 text-xs mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                @endforeach
                                
                                @error('customSections') <span data-validation-error="true" class="text-danger-100 text-xs block mt-1">{{ $message }}</span> @enderror

                                <button type="button" wire:click="addCustomSection" class="w-full py-4 border-2 border-dashed border-card-border rounded-xl text-text-60 hover:text-secondary-200 hover:border-brand-200 hover:bg-brand-50/50 transition-all flex items-center justify-center gap-2 group focus:outline-none text-web-button">
                                    <x-icons.add class="w-4 h-4" />
                                    {{ __('Add New Section') }}
                                </button>
                            </div>

                            <div class="pt-3 mt-2 border-t border-bg-border/60 flex justify-end">
                                <button type="submit" wire:loading.attr="disabled" class="flex items-center justify-center gap-2 min-w-[220px] px-8 py-3 bg-secondary-200 text-brand-10 rounded-md hover:bg-secondary-300 transition-all text-web-button shadow-sm transform active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed">
                                    <span wire:loading.remove wire:target="saveCustomTemplate">
                                        {{ $editingTemplateId ? __('Update Structure') : __('Build This Structure') }}
                                    </span>
                                    <span wire:loading.flex wire:target="saveCustomTemplate" class="items-center justify-center gap-2.5">
                                        <svg class="animate-spin h-4 w-4 shrink-0 text-brand-10" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        <span>{{ __('Saving...') }}</span>
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <x-confirm-dialog
        eventName="open-delete-template-dialog"
        title="{{ __('Delete this Structure?') }}"
        description="Are you sure you want to permanently delete this narrative structure? This action cannot be undone."
        confirmText="Yes, Delete"
        cancelText="Cancel"
        submitAction="confirmDeleteTemplate"
    >
        <x-slot:icon>
            <x-icons.delete class="w-15 h-15" />
        </x-slot:icon>
    </x-confirm-dialog>

    <x-confirm-dialog
        eventName="show-template-in-use-warning"
        title="{{ __('Structure is in Use') }}"
        description="{{ __('This template cannot be deleted because it is currently being used by one or more of your projects. You must change the structure in those projects first.') }}"
        confirmText="{{ __('Understood') }}"
        iconColor="text-warning-100"
        iconBg="bg-warning-100/20"
        btnColor="bg-secondary-150 text-subtext-60 hover:bg-secondary-200"
        :showCancel="false"
    >
        <x-slot:icon>
            <x-icons.alert size="w-15 h-15" color="warning-100"/>
        </x-slot:icon>
    </x-confirm-dialog>
</div>
