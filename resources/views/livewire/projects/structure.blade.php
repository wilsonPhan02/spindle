<?php
use Livewire\Volt\Component;
use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

new #[Layout('layouts.app')] class extends Component {
    public Project $project;

    public function mount(Project $project) {
        $this->project = $project;
    }

    #[On('template-applied')]
    public function refreshProject() {
        $this->project->refresh(); 
    }
    
    public function addTemplate() {
        $this->dispatch('open-template-modal', projectId: $this->project->project_id);
    }
}; ?>

<div>
    <style>
        .bg-dot-pattern {
            background-color: var(--color-brand-10); 
            background-image: radial-gradient(var(--color-brand-100) 1.5px, transparent 1.5px);
            background-size: 24px 24px;
        }
    </style>

    <div class="p-6 lg:p-10 max-w-7xl mx-auto">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $project->title, 'url' => route('projects.show', $project->project_id) ,'truncate' => true],
            ['label' => 'Structure']            
        ]" />

        <div class="flex justify-between items-end mb-6">
            <h1 class="text-app-title-1 text-text-80">Chapter Structure</h1>
            
            <button wire:click="addTemplate" class="flex items-center px-3 py-2 gap-2 text-web-button text-text-60 hover:bg-brand-100 transition-colors rounded-sm max-w-[240px]"
            title="{{ $project->template ? $project->template->name : 'Add Template' }}"
            >
                <span class="truncate min-w-0">
                    {{ $project->template ? $project->template->name : 'Add Template' }}
                </span>
                <x-icons.rename class="shrink-0" />
            </button>
        </div>

        @if(!$project->template_id)
            <div class="relative w-full h-[calc(82vh-120px)] bg-dot-pattern border border-brand-100 rounded-md flex flex-col items-center justify-center shadow-sm">
                <x-icons.no-structure class="mb-6"/>
                <h2 class="text-app-heading-2 text-brand-200 mb-2">You Didn't Use Any Structure!</h2>
                <p class="text-app-feature text-brand-200 mb-2">Choose your structure now and start writing</p>
                <button wire:click="addTemplate" class="flex items-center gap-4 bg-secondary-100 hover:bg-secondary-150 text-bg-main px-6 py-2.5 rounded-md text-app-body-medium transition-colors mt-4 shadow-sm">
                    <x-icons.add-default size="w-4 h-4"/> Add Structure
                </button>
            </div>
        @else
            <div class="relative w-full h-[calc(82vh-120px)] bg-dot-pattern border border-brand-100 rounded-md shadow-sm overflow-hidden">
                <livewire:components.structure-canvas :project="$project" />
            </div>
        @endif
    </div>

    <livewire:components.add-template />
</div>