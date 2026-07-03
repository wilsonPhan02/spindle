<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {

    public $archivedSections = [];

    public function mount() {
        $this->loadArchived();
    }

    public function loadArchived() {
        // Ambil section yang diarsip ATAU memiliki project yang diarsip
        $this->archivedSections = Auth::user()->sections()
            ->where(function ($query) {
                $query->whereNotNull('archived_at')
                      ->orWhereHas('projects', function ($q) {
                          $q->whereNotNull('archived_at');
                      });
            })
            ->with('projects') // Load SEMUA project dulu, lalu kita filter di memori
            ->get()
            ->map(function ($section) {
                if ($section->archived_at === null) {
                    // Jika section masih aktif, TAMPILKAN HANYA project yang diarsip
                    $section->setRelation('projects', $section->projects->whereNotNull('archived_at')->values());
                } else {
                    // Jika section sudah diarsip, TAMPILKAN SEMUA project-nya
                    $section->setRelation('projects', $section->projects->values());
                }
                return $section;
            })
            ->filter(function ($section) {
                // Pastikan kita tidak menampilkan section yang kosong
                return $section->projects->count() > 0;
            })
            ->sortByDesc(function ($section) {
                // Urutkan berdasarkan yang paling baru diarsip
                $latestProject = $section->projects->max('archived_at');
                return max($section->archived_at, $latestProject);
            })->values();
    }

    public function restoreSection($sectionId) {
        $section = Auth::user()->sections()->find($sectionId);
        if ($section) {
            $section->update(['archived_at' => null]);
            $section->projects()->whereNotNull('archived_at')->update(['archived_at' => null]);
            $this->loadArchived();
            $this->dispatch('project-updated');
        }
    }

    public function deleteSection($sectionId) {
        $section = Auth::user()->sections()->find($sectionId);
        if ($section) {
            // Jika section-nya diarsip, hapus section (otomatis menghapus projects karena cascade)
            if ($section->archived_at !== null) {
                $section->delete();
            } else {
                // Jika section aktif, hapus HANYA project yang diarsip
                $section->projects()->whereNotNull('archived_at')->delete();
            }
            $this->loadArchived();
            $this->dispatch('project-updated');
        }
    }

    public function restoreProject($projectId) {
        $project = Project::where('user_id', Auth::id())->find($projectId);
        if ($project) {
            $project->update(['archived_at' => null]);
            // Jika kita merestore project dari section yang terarsip, restore sectionnya juga
            if ($project->section && $project->section->archived_at !== null) {
                $project->section->update(['archived_at' => null]);
            }
            $this->loadArchived();
            $this->dispatch('project-updated');
        }
    }

    public function deleteProject($projectId) {
        $project = Project::where('user_id', Auth::id())->find($projectId);
        if ($project) {
            $project->delete();
            $this->loadArchived();
            $this->dispatch('project-updated');
        }
    }
}; ?>

<div class="p-10 max-w-7xl mx-auto">

    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Archive']
    ]" />

    @if(count($archivedSections) === 0)
        {{-- Empty State --}}
        <div class="flex flex-col items-center justify-center mt-16">
            <x-empty-archive class="w-64 h-64 mb-6 opacity-80" />
            <h2 class="text-app-heading-1 text-subtext-80 mb-2">Your archive is empty</h2>
            <p class="text-app-body-large text-subtext-70">Archived sections will appear here.</p>
        </div>
    @else
        <div class="space-y-12">
            @foreach($archivedSections as $section)
                <div class="relative">

                    {{-- Section Header --}}
                    <div class="flex justify-between items-center border-b border-brand-150 pb-2 mb-6" x-data="{ menuOpen: false }">
                        <div class="min-w-0 flex-1">
                            <h2 class="text-2xl font-merriweather text-text-100 truncate">
                                {{ $section->title }}
                            </h2>
                            <p class="text-[12px] text-subtext-90 mt-0.5">
                                @if($section->archived_at)
                                    Archived {{ $section->archived_at->diffForHumans() }} &middot;
                                @else
                                    Contains archived projects &middot;
                                @endif
                                {{ $section->projects->count() }} {{ $section->projects->count() === 1 ? 'project' : 'projects' }}
                            </p>
                        </div>

                        <div class="relative shrink-0 ml-4">
                            <button @click="menuOpen = !menuOpen" @click.away="menuOpen = false" class="p-1 hover:bg-brand-150 rounded-md transition-colors">
                                <svg class="w-6 h-6 text-text-80" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
                            </button>

                            <div x-show="menuOpen" style="display: none;" class="absolute right-0 mt-2 w-48 bg-white border border-brand-150 rounded-lg shadow-lg z-20 py-1 overflow-hidden">
                                <button
                                    wire:click="restoreSection('{{ $section->section_id }}')"
                                    @click="menuOpen = false"
                                    class="w-full text-left px-4 py-2 text-app-body-medium text-text-80 hover:bg-brand-10 flex items-center gap-3"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Restore All
                                </button>
                                <button
                                    wire:click="deleteSection('{{ $section->section_id }}')"
                                    wire:confirm="Hapus semua project yang diarsip dari section ini secara permanen? Tindakan ini tidak dapat dibatalkan."
                                    @click="menuOpen = false"
                                    class="w-full text-left px-4 py-2 text-app-body-medium text-danger-100 hover:bg-danger-100/5 flex items-center gap-3"
                                >
                                    <x-icons.delete class="w-4 h-4" />
                                    Delete All
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Projects Grid (mirip dashboard) --}}
                    <div class="flex gap-6 overflow-x-auto pb-4 custom-scrollbar">

                        @forelse($section->projects as $project)
                            <div class="w-44 shrink-0 group" x-data="{ menuOpen: false }">
                                <div class="relative">
                                    {{-- Book Cover --}}
                                    <div class="w-full aspect-[2/3] bg-[#B69F78] rounded-r-xl rounded-l-sm border-l-[6px] border-[#705D42] shadow-md relative mb-3 opacity-75 group-hover:opacity-95 transition-all duration-200">
                                        @if($project->cover_image_path)
                                            <img src="{{ Storage::url($project->cover_image_path) }}" class="absolute inset-y-0 left-0 right-0 w-full h-full object-cover rounded-r-xl rounded-l-sm" />
                                        @endif
                                        <div class="absolute inset-1 border border-[#D5C6A9] opacity-40 rounded-r-lg pointer-events-none"></div>
                                        <div class="absolute top-1.5 left-1.5 w-4 h-4 border-t border-l border-[#E2D6C0] opacity-60"></div>
                                        <div class="absolute bottom-1.5 right-1.5 w-4 h-4 border-b border-r border-[#E2D6C0] opacity-60"></div>
                                        <div class="absolute right-0 top-0 bottom-0 w-1.5 bg-white opacity-40 rounded-r-xl"></div>
                                    </div>

                                    {{-- Restore/Delete overlay --}}
                                    <div class="absolute inset-x-0 bottom-3 flex flex-col gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-200 px-3">
                                        <button
                                            wire:click="restoreProject('{{ $project->project_id }}')"
                                            class="w-full flex items-center justify-center gap-1.5 py-1.5 bg-[#1F2328]/90 backdrop-blur-sm rounded-lg text-[12px] text-white font-medium hover:bg-[#1F2328] transition-colors shadow-lg"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            Restore
                                        </button>
                                        <button
                                            wire:click="deleteProject('{{ $project->project_id }}')"
                                            wire:confirm="Hapus project ini secara permanen? Tindakan ini tidak dapat dibatalkan."
                                            class="w-full flex items-center justify-center gap-1.5 py-1.5 bg-danger-100/90 backdrop-blur-sm rounded-lg text-[12px] text-white font-medium hover:bg-danger-100 transition-colors shadow-lg"
                                        >
                                            <x-icons.delete class="w-3 h-3 text-white" />
                                            Delete
                                        </button>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 mb-1">
                                    <x-icons.sidebar-book class="w-4 h-4 text-text-80 shrink-0" />
                                    <h3 class="text-app-body-medium text-text-100 truncate">{{ $project->title }}</h3>
                                </div>
                                <p class="text-[11px] text-subtext-70">{{ $project->created_at->format('d F Y') }}</p>
                            </div>
                        @empty
                            <div class="flex items-center justify-center w-full py-8 text-subtext-90 text-app-body-medium italic">
                                No projects in this section.
                            </div>
                        @endforelse

                    </div>

                </div>
            @endforeach
        </div>
    @endif



</div>
