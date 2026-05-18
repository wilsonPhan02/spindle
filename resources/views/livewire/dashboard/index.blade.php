<?php

use Livewire\Volt\Component;
use App\Models\Section;

new class extends Component {
    public $sections = [];

    public function mount() {
        $this->loadSections();
    }

    public function loadSections() {
        $this->sections = auth()->user()->sections()->orderBy('created_at')->get();
    }

    public function addSection() {
        auth()->user()->sections()->create([
            'title' => 'Untitled Section'
        ]);
        $this->loadSections();
    }

    public function renameSection($sectionId, $newTitle) {
        $section = auth()->user()->sections()->find($sectionId);
        if ($section && trim($newTitle) !== '') {
            $section->update(['title' => trim($newTitle)]);
        }
        $this->loadSections();
    }
}; ?>

<div class="p-10 max-w-7xl mx-auto">

    <header class="flex justify-between items-center mb-8">
        <span class="text-app-heading-2 text-text-100 font-semibold">Dashboard</span>

        <x-logo class="h-8 w-auto text-text-100" />
    </header>

    @php
        $displayName = Auth::user()->profile?->username ?? explode('@', Auth::user()->email)[0];
        $nameLen = strlen($displayName);

        // Logika ukuran font: Mengecil perlahan sesuai panjang karakter
        $titleSize = 'text-3xl lg:text-4xl';
        if ($nameLen >= 20) {
            $titleSize = 'text-xl lg:text-2xl'; // Mentok di ukuran ini
        } elseif ($nameLen >= 12) {
            $titleSize = 'text-2xl lg:text-3xl';
        }
    @endphp

    <div class="bg-[#F5EFE9] rounded-xl overflow-hidden mb-8 shadow-sm h-[200px] flex justify-between items-end">

        <div class="w-48 md:w-56 lg:w-60 mb-2 shrink-0 pointer-events-none">
            <x-left-dashboard class="w-full h-auto block" />
        </div>

        <div class="flex-1 self-center text-center px-4 md:px-8 z-10 min-w-0 flex flex-col items-center">

            <h1
                class="{{ $titleSize }} font-merriweather text-text-100 mb-2 w-full truncate transition-all duration-300"
                title="Welcome {{ $displayName }}!"
            >
                Welcome <span class="font-bold">{{ $displayName }}!</span>
            </h1>

            <p class="text-app-body-large text-text-80 truncate w-full">Are u ready to spin the <span class="italic">yarn</span>?</p>
        </div>

        <div class="w-48 md:w-56 lg:w-60 shrink-0 pointer-events-none flex justify-end">
            <x-right-dashboard class="w-full h-auto block" />
        </div>

    </div>

    @if(count($sections) === 0)

        <button wire:click="addSection" class="w-full py-3 mb-16 border border-brand-200 rounded-lg text-subtext-80 hover:bg-[#F5EFE9] transition-colors flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add New Section
        </button>

        <div class="flex flex-col items-center justify-center mt-12">
            <x-empty-dashboard class="w-64 h-64 mb-6 opacity-80" />
            <h2 class="text-app-heading-1 text-subtext-80 mb-2">Looks like it's a bit quiet in here...</h2>
            <p class="text-app-body-large text-subtext-70">Ready to create a new one?</p>
        </div>

    @else

        <div class="space-y-12 mb-8">
            @foreach($sections as $section)
                <div x-data="{ editing: false, newName: '{{ $section->title }}', menuOpen: false }" class="relative">

                    <div class="flex justify-between items-center border-b border-brand-150 pb-2 mb-6">
                        <div class="w-full">
                            <h2
                                x-show="!editing"
                                @dblclick="editing = true; setTimeout(() => $refs.nameInput.focus(), 50)"
                                class="text-2xl font-merriweather text-text-100 cursor-pointer select-none hover:text-secondary-200 transition-colors"
                                title="Double click to rename"
                            >
                                {{ $section->title }}
                            </h2>

                            <input
                                x-show="editing"
                                x-model="newName"
                                x-ref="nameInput"
                                @keydown.enter="$wire.renameSection('{{ $section->section_id }}', newName); editing = false"
                                @keydown.escape="editing = false; newName = '{{ $section->title }}'"
                                @click.away="$wire.renameSection('{{ $section->section_id }}', newName); editing = false"
                                class="text-2xl font-merriweather text-text-100 bg-transparent border-b border-secondary-200 outline-none w-1/2 focus:ring-0 px-0 py-0"
                            >
                        </div>

                        <div class="relative shrink-0">
                            <button @click="menuOpen = !menuOpen" @click.away="menuOpen = false" class="p-1 hover:bg-brand-150 rounded-md transition-colors">
                                <svg class="w-6 h-6 text-text-80" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
                            </button>

                            <div x-show="menuOpen" style="display: none;" class="absolute right-0 mt-2 w-48 bg-white border border-brand-150 rounded-lg shadow-lg z-10 py-1">
                                <button class="w-full text-left px-4 py-2 text-app-body-medium text-text-80 hover:bg-brand-10 flex items-center gap-3">
                                    <x-icons.add class="w-4 h-4" /> Add Project
                                </button>
                                <button @click="editing = true; menuOpen = false; setTimeout(() => $refs.nameInput.focus(), 50)" class="w-full text-left px-4 py-2 text-app-body-medium text-text-80 hover:bg-brand-10 flex items-center gap-3">
                                    <x-icons.rename class="w-4 h-4" /> Rename
                                </button>
                                <button class="w-full text-left px-4 py-2 text-app-body-medium text-text-80 hover:bg-brand-10 flex items-center gap-3">
                                    <x-icons.archive class="w-4 h-4" /> Archive
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-6">
                        <div class="w-44 group cursor-pointer">
                            <div class="w-full aspect-[2/3] bg-[#B69F78] rounded-r-xl rounded-l-sm border-l-[6px] border-[#705D42] shadow-md relative mb-3 group-hover:shadow-lg transition-all group-hover:-translate-y-1">
                                <div class="absolute inset-1 border border-[#D5C6A9] opacity-40 rounded-r-lg pointer-events-none"></div>
                                <div class="absolute top-1.5 left-1.5 w-4 h-4 border-t border-l border-[#E2D6C0] opacity-60"></div>
                                <div class="absolute bottom-1.5 right-1.5 w-4 h-4 border-b border-r border-[#E2D6C0] opacity-60"></div>
                                <div class="absolute right-0 top-0 bottom-0 w-1.5 bg-white opacity-40 rounded-r-xl"></div>
                            </div>

                            <div class="flex items-center gap-2 mb-1">
                                <svg class="w-4 h-4 text-text-80 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H8V4h12v12z"/></svg>
                                <h3 class="text-app-body-medium text-text-100 truncate">Untitled Project</h3>
                            </div>
                            <p class="text-[11px] text-subtext-70">30 July 2026</p>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>

        <button wire:click="addSection" class="w-full py-3 border border-brand-200 bg-[#EFEBE6] rounded-lg text-subtext-80 hover:bg-[#E5DED5] transition-colors flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add New Section
        </button>

    @endif
</div>
