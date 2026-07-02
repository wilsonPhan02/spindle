<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {
    public $pinnedProjects = [];
    public $recentProjects = [];

    public function mount()
    {
        $this->loadProjects();
    }

    #[On('project-pinned-updated')]
    public function loadProjects()
    {
        $user = auth()->user();
        if ($user) {
            $this->pinnedProjects = $user->projects()
                ->where('is_pinned', true)
                ->whereNull('archived_at')
                ->latest('updated_at')
                ->get();

            // Just fetch recent 5 for now, unless pinned logic needs to be completely separate
            $this->recentProjects = $user->projects()
                ->whereNull('archived_at')
                ->latest('updated_at')
                ->take(5)
                ->get();
        }
    }

    public function unpin($projectId)
    {
        $project = auth()->user()->projects()->find($projectId);
        if ($project) {
            $project->is_pinned = false;
            $project->save();
            $this->loadProjects();
            $this->dispatch('project-pinned-updated'); // to update detail view if active
        }
    }
}; ?>
<aside
    @mouseleave="isHovered = false"
    class="fixed inset-y-0 left-0 z-50 w-72 bg-brand-100 border-r border-brand-150 transition-transform duration-300 ease-in-out flex flex-col shadow-sm"
    :class="(isPinned || isHovered) ? 'translate-x-0' : '-translate-x-full'"
>
    <button
        @click="isPinned = false; isHovered = false"
        class="absolute right-0 top-1/2 translate-x-1/2 -translate-y-1/2 w-8 h-16 bg-brand-100 border border-brand-150 rounded-full flex items-center justify-start pl-1.5 text-text-80 hover:bg-brand-150 transition-colors z-50 shadow-sm focus:outline-none"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    </button>

    <div class="p-6">
        <div class="flex items-center space-x-3">
            <div>
                <x-avatar
                    size="w-12 h-12"
                    :imageUrl="auth()->user()->profile?->avatar_url ? Storage::url(auth()->user()->profile->avatar_url) : null"                />
            </div>

            <div class="flex flex-col truncate">
                <span class="text-app-subheading-2 text-text-80 truncate" :title="currentUsername" x-text="currentUsername">
                {{ Auth::user()->profile?->username ?? explode('@', Auth::user()->email)[0] }}
                </span>

                <span class="text-app-body-small text-subtext-90 truncate" title="{{ Auth::user()->email }}">
                {{ Auth::user()->email }}
                </span>
            </div>
        </div>
    </div>

    <div class="px-6 mb-8">
        <div class="relative">
            <svg class="absolute left-3 top-2.5 w-4 h-4 text-subtext-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" placeholder="Search The Yarn" class="w-full pl-9 pr-4 py-2 bg-brand-10 border-none rounded-full text-app-body-medium text-text-80 placeholder-subtext-70 focus:ring-1 focus:ring-secondary-150 outline-none transition-shadow">
        </div>
    </div>

    <div class="flex-1 overflow-y-auto px-6 space-y-6 pb-6 custom-scrollbar">

        <div x-data="{ open: true, viewAll: false }">
            <button @click="open = !open" class="flex items-center justify-between w-full text-app-feature text-text-70 mb-2 focus:outline-none hover:text-text-80 transition-colors">
                <span>Pinned <span class="text-[10px] ml-1 opacity-70">{{ count($pinnedProjects) }}/10</span></span>
                <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-collapse>
                @if(count($pinnedProjects) > 0)
                    {{-- Default: show first 5 only, no scroll --}}
                    <div x-show="!viewAll" class="space-y-1">
                        @foreach($pinnedProjects->take(5) as $pProject)
                            <div class="group flex items-center justify-between px-2 py-1.5 -mx-2 rounded-lg hover:bg-brand-150 transition-colors">
                                <a href="{{ route('projects.show', $pProject->project_id) }}" wire:navigate class="flex items-center gap-2 flex-1 min-w-0">
                                    <x-icons.sidebar-book class="w-4 h-4 text-text-70 shrink-0" />
                                    <span class="text-[13px] font-medium text-text-80 truncate group-hover:text-text-100 transition-colors">{{ $pProject->title }}</span>
                                </a>
                                <button wire:click="unpin('{{ $pProject->project_id }}')" class="opacity-0 group-hover:opacity-100 text-subtext-70 hover:text-[#8C7558] transition-all p-1 shrink-0" title="Unpin Project">
                                    <x-icons.bookmark-slash class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        @endforeach
                        @if(count($pinnedProjects) > 5)
                            <button @click="viewAll = true" class="w-full text-center text-[11px] font-semibold text-[#8C7558] hover:text-[#5E4C38] py-1.5 transition-colors">
                                View all ({{ count($pinnedProjects) }})
                            </button>
                        @endif
                    </div>

                    {{-- View All: show all with vertical scroll --}}
                    <div x-show="viewAll" x-cloak class="max-h-[280px] overflow-y-auto overflow-x-hidden custom-scrollbar space-y-1">
                        @foreach($pinnedProjects as $pProject)
                            <div class="group flex items-center justify-between px-2 py-1.5 -mx-2 rounded-lg hover:bg-brand-150 transition-colors">
                                <a href="{{ route('projects.show', $pProject->project_id) }}" wire:navigate class="flex items-center gap-2 flex-1 min-w-0">
                                    <x-icons.sidebar-book class="w-4 h-4 text-text-70 shrink-0" />
                                    <span class="text-[13px] font-medium text-text-80 truncate group-hover:text-text-100 transition-colors">{{ $pProject->title }}</span>
                                </a>
                                <button wire:click="unpin('{{ $pProject->project_id }}')" class="opacity-0 group-hover:opacity-100 text-subtext-70 hover:text-[#8C7558] transition-all p-1 shrink-0" title="Unpin Project">
                                    <x-icons.bookmark-slash class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        @endforeach
                        <button @click="viewAll = false" class="w-full text-center text-[11px] font-semibold text-[#8C7558] hover:text-[#5E4C38] py-1.5 transition-colors">
                            Show less
                        </button>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-4 text-center opacity-60">
                        <x-icons.sidebar-pen class="w-8 h-8 text-secondary-150 mb-1" />
                        <p class="text-app-desc-feature text-secondary-200">No Pinned Projects!</p>
                    </div>
                @endif
            </div>
        </div>

        <div x-data="{ open: true }">
            <button @click="open = !open" class="flex items-center justify-between w-full text-app-feature text-text-70 mb-2 focus:outline-none hover:text-text-80 transition-colors">
                <span>Recent</span>
                <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-collapse>
                @if(count($recentProjects) > 0)
                    <div class="max-h-[160px] overflow-y-auto custom-scrollbar space-y-1 pr-1 -mr-1">
                        @foreach($recentProjects as $rProject)
                            <a href="{{ route('projects.show', $rProject->project_id) }}" wire:navigate class="flex items-center gap-2 px-2 py-1.5 -mx-2 rounded-lg hover:bg-brand-150 transition-colors group">
                                <x-icons.sidebar-book class="w-4 h-4 text-text-70 shrink-0" />
                                <span class="text-[13px] font-medium text-text-80 truncate group-hover:text-text-100 transition-colors">{{ $rProject->title }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-4 text-center opacity-60">
                        <x-icons.sidebar-pen class="w-8 h-8 text-secondary-150 mb-1" />
                        <p class="text-app-desc-feature text-secondary-200">You Didn't Have Any Project!</p>
                    </div>
                @endif
            </div>
        </div>

        <div>
            <div class="text-app-feature text-text-70 mb-2">Others</div>
            <div class="space-y-1">
                <a href="{{ route('archive') }}" wire:navigate class="flex items-center px-3 py-2 -mx-3 rounded-lg text-app-body-medium text-text-80 hover:bg-brand-150 hover:text-text-100 transition-colors group {{ request()->routeIs('archive') ? 'bg-brand-150 text-text-100' : '' }}">
                    <x-icons.archive class="w-5 h-5 mr-3 text-text-80 group-hover:text-text-100 transition-colors" />
                    Archive
                </a>

                <a href="{{ route('settings') }}" class="flex items-center px-3 py-2 -mx-3 rounded-lg text-app-body-medium text-text-80 hover:bg-brand-150 hover:text-text-100 transition-colors group">
                    <x-icons.setting class="w-5 h-5 mr-3 text-text-80 group-hover:text-text-100 transition-colors" />
                    Settings
                </a>
            </div>
        </div>
    </div>
</aside>
