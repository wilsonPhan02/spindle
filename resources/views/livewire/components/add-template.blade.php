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
            'title' => 'Section ' . $newIndex, 
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
        $this->validate([
            'customTemplateName' => 'required|string|max:100',
            'customTemplateDescription' => 'nullable|string',
            'customImagePreview' => 'nullable|image|max:2048',
            'customSections' => 'required|array|min:1',
            'customSections.*.title' => 'required|string|max:40',
            'customSections.*.goal' => 'nullable|string',
        ]);

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
                StructureSection::where('template_id', $template->template_id)
                    ->whereNotIn('structure_section_id', $keptSectionIds)
                    ->delete();

                foreach ($this->customSections as $index => $section) {
                    if (!empty($section['id'])) {
                        StructureSection::where('structure_section_id', $section['id'])
                            ->update(['order_index' => $index + 1, 'title' => $section['title'], 'goal' => $section['goal']]);
                    } else {
                        StructureSection::create([
                            'template_id' => $template->template_id,
                            'order_index' => $index + 1,
                            'title' => $section['title'],
                            'goal' => $section['goal']
                        ]);
                    }
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
                                            @if($template->is_custom)
                                                <img src="{{ Storage::url($template->image_preview) }}" alt="Preview" class="w-full h-full object-cover rounded-md opacity-80" />
                                            @else
                                                <x-dynamic-component :component="$template->image_preview" class="w-full max-w-lg h-auto" />
                                            @endif
                                        @else
                                            <div class="flex flex-col items-center justify-center h-48 text-brand-200">
                                                <x-icons.no-structure class="w-12 h-12 opacity-50 mb-2"/>
                                                <span class="text-app-desc-feature">No Preview Available</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="absolute bottom-8 left-0 bg-bg-main px-6 py-2.5 rounded-r-lg shadow-lg opacity-0 group-hover:opacity-100 transition-all transform -translate-x-2 group-hover:translate-x-0 duration-300 max-w-sm">
                                        <span class="block truncate w-full text-web-body-large text-text-60" title="{{ $template->name }}">
                                            {{ $template->name }}
                                        </span>
                                    </div>

                                    @if($template->is_custom)
                                        <div class="absolute top-3 right-3 flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-all transform translate-y-2 group-hover:translate-y-0 duration-300">
                                            
                                            <button @click.stop wire:click="editCustomTemplate('{{ $template->template_id }}')" 
                                                    class="flex items-center gap-1 px-2 py-1 bg-bg-main/95 backdrop-blur-sm border border-brand-200 text-secondary-200 rounded-sm hover:bg-brand-200 transition-all shadow-md" 
                                                    title="Edit Structure">
                                                <x-icons.rename class="w-3 h-3"/>
                                                <span class="text-app-caption font-semibold">Edit</span>
                                            </button>
                                            
                                            <button @click.stop wire:click="attemptDeleteTemplate('{{ $template->template_id }}')" 
                                                    class="flex items-center gap-2 px-2 py-1 bg-bg-main/95 backdrop-blur-sm border border-danger-100/50 text-danger-100 rounded-sm hover:bg-danger-100 hover:border-danger-100 hover:text-brand-50 transition-all shadow-md group/btn" 
                                                    title="Delete Structure">
                                                <x-icons.delete-default size="w-3 h-3" color="currentColor"/>
                                                <span class="text-app-caption font-semibold">Delete</span>
                                            </button>
                                            
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="shrink-0 p-6 flex justify-center bg-bg-main z-10 border-t border-bg-border/60">
                        <button wire:click="goToStep4" class="flex items-center gap-2 px-6 py-2.5 bg-transparent border border-brand-200 text-secondary-200 rounded-md hover:bg-brand-50 hover:text-secondary-300 transition-colors text-web-button">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Create Template
                        </button>
                    </div>
                @endif

                @if($step === 2 && $this->selectedTemplate)
                    <div class="shrink-0 pt-6 px-6 relative z-10 bg-bg-main">
                        <button wire:click="goBack" class="w-10 h-10 rounded-full border border-brand-200 text-text-60 hover:bg-brand-50 hover:text-text-100 flex items-center justify-center transition-colors bg-bg-main" title="Back to structures">
                            <x-icons.back/>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar px-10 md:px-14 pb-14 bg-bg-main">
                        <div class="flex flex-col items-center max-w-4xl mx-auto w-full">
                            
                            <div class="text-center mb-10 w-full mt-4 max-w-xl mx-auto">
                                <h1 class="block w-full text-3xl font-bold text-text-100 mb-6 break-words" title="{{ $this->selectedTemplate->name }}">
                                    {{ $this->selectedTemplate->name }}
                                </h1>
                                <button wire:click="useTemplate" class="px-5 py-2 border border-brand-200 text-secondary-200 rounded-md hover:bg-brand-50 hover:text-secondary-300 transition-colors text-web-button">
                                    Use Template
                                </button>
                            </div>

                            <div class="w-full max-w-xl h-[60vh] bg-[#1C1C1C] rounded-xl p-8 flex items-center justify-center mb-8">
                                @if($this->selectedTemplate->image_preview)
                                    @if($this->selectedTemplate->is_custom)
                                        <img src="{{ Storage::url($this->selectedTemplate->image_preview) }}" alt="Preview" class="w-full h-full object-contain" />
                                    @else
                                        <x-dynamic-component :component="$this->selectedTemplate->image_preview" class="w-full h-auto max-w-lg" />
                                    @endif
                                @else
                                    <div class="flex flex-col items-center justify-center h-48 text-brand-200">
                                        <x-icons.no-structure class="w-12 h-12 opacity-50 mb-2"/>
                                        <span class="text-app-desc-feature">No Preview Available</span>
                                    </div>
                                @endif
                            </div>
                            
                            
                            <div class="w-full mb-12 px-3">
                                <hr class="mb-4 border-text-60 border-t-1">
                                @if ($this->selectedTemplate->description)
                                    <p class="text-web-body-small text-text-70 leading-relaxed whitespace-pre-wrap">{{ $this->selectedTemplate->description }}</p>
                                @else
                                    <p class="text-center text-web-body-small text-text-70 leading-relaxed whitespace-pre-wrap">No description for this template</p>
                                @endif
                                <hr class="mt-4 border-text-60 border-t-1">
                            </div>
                            

                            <div class="w-full flex flex-col gap-10 px-3">
                                @forelse($this->selectedTemplate->sections as $section)
                                    <div>
                                        <h3 class="text-web-heading-1 text-text-90 mb-4">
                                            {{ $section->title }}
                                        </h3>
                                        @if ($section->goal)
                                            <div class="prose prose-stone prose-p:text-text-70 prose-li:text-text-70 prose-strong:text-text-80 max-w-none text-web-body-small leading-relaxed">
                                                {!! Str::markdown($section->goal) !!}
                                            </div>
                                        @else
                                            <p class=" text-text-60 italic text-app-body-small">No details for this section yet.</p>
                                        @endif
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

                        <h3 class="text-app-heading-1 text-text-90 mb-4">
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
                
                @if($step === 4)
                    <div class="shrink-0 pt-6 px-6 relative z-10 bg-bg-main flex items-center border-b border-bg-border/60 pb-4">
                        <button wire:click="goBack" class="w-10 h-10 rounded-full border border-brand-200 text-text-60 hover:bg-brand-50 hover:text-text-100 flex items-center justify-center transition-colors bg-bg-main" title="Back to structures">
                            <x-icons.back/>
                        </button>
                        <h2 class="text-app-title-1 text-text-90 ml-6">Architect Your Structure</h2>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar px-10 md:px-14 py-8 bg-bg-main">
                        <form wire:submit.prevent="saveCustomTemplate" class="max-w-3xl mx-auto flex flex-col gap-8 pb-10">
                            
                            <div class="flex flex-col gap-4">
                                <div>
                                    <label class="block text-app-desc-feature font-bold text-text-70 uppercase tracking-wider mb-2">Template Name <span class="text-danger-100">*</span></label>
                                    <input type="text" wire:model="customTemplateName" placeholder="e.g. My Hero's Journey" class="w-full bg-card-bg border border-card-border rounded-lg px-4 py-3 text-text-90 placeholder-text-60/40 focus:outline-none focus:border-secondary-200 focus:ring-1 focus:ring-secondary-200 transition-all text-app-body-medium">
                                    @error('customTemplateName') <span class="text-danger-100 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                
                                <div>
                                    <label class="block text-app-desc-feature font-bold text-text-70 uppercase tracking-wider mb-2">Description</label>
                                    <textarea wire:model="customTemplateDescription" rows="2" placeholder="Briefly explain what this structure is for..." class="w-full bg-card-bg border border-card-border rounded-lg px-4 py-3 text-text-90 placeholder-text-60/40 focus:outline-none focus:border-secondary-200 focus:ring-1 focus:ring-secondary-200 transition-all custom-scrollbar resize-none text-app-body-medium"></textarea>
                                </div>

                                <div>
                                    <label class="block text-app-desc-feature font-bold text-text-70 uppercase tracking-wider mb-2">Cover Image</label>
                                    <div class="relative group w-full h-45 border-2 border-dashed border-card-border rounded-lg bg-card-bg hover:bg-card-hover hover:border-brand-200 transition-colors flex flex-col items-center justify-center overflow-hidden cursor-pointer">
                                        @if ($customImagePreview || $existingImagePath)
                                            <button type="button" 
                                                wire:click.prevent="removeImage" 
                                                class="absolute top-3 right-3 z-30 p-1.5 pt-0 bg-danger-100/90 text-white rounded-md hover:bg-danger-100 transition-all shadow-md opacity-0 group-hover:opacity-100" 
                                                title="Remove Image">
                                            <x-icons.delete-default size="w-4 h-4" color="currentColor"/>
                                        </button>
                                            <input type="file" wire:model="customImagePreview" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

                                            @if ($customImagePreview)
                                                <img src="{{ $customImagePreview->temporaryUrl() }}" class="absolute inset-0 w-full h-full object-cover opacity-100">
                                                <div class="absolute bottom-2 left-2 z-20 bg-bg-main/90 px-2 py-1 rounded text-app-caption text-text-90 font-medium pointer-events-none opacity-0 group-hover:opacity-100">
                                                    New Image (Click to replace)
                                                </div>
                                            @elseif ($existingImagePath)
                                                <img src="{{ Storage::url($existingImagePath) }}" class="absolute inset-0 w-full h-full object-cover opacity-100">
                                                <div class="absolute bottom-2 left-2 z-20 bg-bg-main/90 px-2 py-1 rounded text-app-caption text-text-90 font-medium pointer-events-none opacity-0 group-hover:opacity-100">
                                                    Current Image (Click to replace)
                                                </div>
                                            @endif

                                        @else
                                            <input type="file" wire:model="customImagePreview" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                            <svg class="w-8 h-8 text-text-40 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span class="text-app-body-small text-text-60">Click or drag to upload an image</span>
                                        @endif
                                    </div>
                                    @error('customImagePreview') <span class="text-danger-100 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <hr class="border-t border-bg-border/60">

                            <div class="flex flex-col gap-5">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-app-heading-1 text-text-90">Narrative Sections</h3>
                                </div>

                                @foreach($customSections as $index => $section)
                                    <div class="bg-card-bg border border-card-border rounded-xl p-5 shadow-sm relative group" wire:key="section-{{ $index }}">
                                        
                                        <div class="flex gap-4 items-start mb-4">
                                            <div class="w-8 h-8 rounded bg-brand-100 text-secondary-200 flex items-center justify-center font-bold text-app-body-small shrink-0 mt-1">
                                                {{ $index + 1 }}
                                            </div>
                                            <div class="flex-1">
                                                <input type="text" wire:model="customSections.{{ $index }}.title" placeholder="e.g. Inciting Incident" class="w-full bg-transparent border-b border-card-border focus:border-secondary-200 px-1 py-1.5 text-text-90 placeholder-text-60/40 focus:outline-none transition-all text-app-body-large">
                                                @error('customSections.'.$index.'.title') <span class="text-danger-100 text-xs mt-1 block">{{ $message }}</span> @enderror
                                            </div>
                                            
                                            @if(count($customSections) > 1)
                                                <button type="button" wire:click="removeCustomSection({{ $index }})" class="text-text-60/70 hover:text-danger-100 p-1.5 pt-0 rounded-md hover:bg-danger-100/10 transition-colors mt-1" title="Remove section">
                                                    <x-icons.delete-default size="w-4 h-4" color="currentColor"/>
                                                </button>
                                            @endif
                                        </div>

                                        <div class="pl-12">
                                            <textarea wire:model="customSections.{{ $index }}.goal" rows="2" placeholder="Describe what needs to happen in this section..." class="w-full bg-bg-main border border-card-border rounded-lg px-3 py-2 text-text-80 placeholder-text-60/40 focus:outline-none focus:border-secondary-200 transition-all custom-scrollbar resize-none text-app-body-small"></textarea>
                                        </div>
                                    </div>
                                @endforeach
                                
                                @error('customSections') <span class="text-danger-100 text-xs">{{ $message }}</span> @enderror

                                <button type="button" wire:click="addCustomSection" class="w-full py-4 border-2 border-dashed border-card-border rounded-xl text-text-60 hover:text-secondary-200 hover:border-brand-200 hover:bg-brand-50/50 transition-all flex items-center justify-center gap-2 group focus:outline-none text-web-button">
                                    <x-icons.add-default class="w-4 h-4"/>
                                    Add New Section
                                </button>
                            </div>

                            <div class="pt-3 mt-2 border-t border-bg-border/60 flex justify-end">
                                <button type="submit" class="flex items-center gap-2 px-8 py-3 bg-secondary-200 text-brand-10 rounded-md hover:bg-secondary-300 transition-all text-web-button shadow-sm transform active:scale-95">
                                    <span wire:loading.remove wire:target="saveCustomTemplate">
                                        {{ $editingTemplateId ? 'Update Structure' : 'Build This Structure' }}
                                    </span>
                                    <span wire:loading wire:target="saveCustomTemplate">Saving...</span>
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
        title="Delete this Structure?"
        description="Are you sure you want to permanently delete this narrative structure? This action cannot be undone."
        confirmText="Yes, Delete"
        cancelText="Cancel"
        submitAction="confirmDeleteTemplate"
    >
        <x-slot:icon>
            <x-icons.delete-default size="w-15 h-15" color="currentColor"/>
        </x-slot:icon>
    </x-confirm-dialog>

    <x-confirm-dialog
        eventName="show-template-in-use-warning"
        title="Structure is in Use"
        description="This template cannot be deleted because it is currently being used by one or more of your projects. You must change the structure in those projects first."
        confirmText="Understood"
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