<?php
use Livewire\Volt\Component;
use App\Models\Project;

new class extends Component {
    public Project $project;

    public function mount(Project $project) {
        $this->project = $project;
    }
}; ?>

<div class="p-10 max-w-7xl mx-auto">
    <header class="flex justify-between items-center mb-10">
        <div class="flex items-center gap-2 text-app-heading-2">
            <a href="{{ route('dashboard') }}" wire:navigate class="text-text-80 hover:text-secondary-200">Dashboard</a>
            <span class="text-text-80">></span>
            <span class="text-text-100 font-semibold">{{ $project->title }}</span>
        </div>
        <x-logo class="h-8 w-auto text-text-100" />
    </header>

    <div class="flex gap-8">
        <div class="w-1/3 aspect-[2/3] bg-[#B69F78] rounded-r-3xl rounded-l-md border-l-[12px] border-[#705D42] shadow-xl relative">
            <div class="absolute inset-2 border-2 border-[#D5C6A9] opacity-40 rounded-r-2xl pointer-events-none"></div>
            </div>

        <div class="w-2/3 bg-[#F5EFE9] rounded-xl p-10 shadow-sm border border-brand-150">
            <h1 class="text-4xl font-merriweather text-text-100 mb-2">{{ $project->title }}</h1>
            <p class="text-text-80 mb-6">from Sailor's Version Series</p>

            <div class="mb-6">
                <span class="text-sm font-semibold text-text-100 uppercase tracking-wider">Synopsis</span>
                <p class="text-text-80 mt-2">Write your synopsis here!</p>
            </div>
        </div>
    </div>
</div>
