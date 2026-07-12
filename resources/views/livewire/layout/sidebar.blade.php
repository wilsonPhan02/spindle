<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Models\ProjectCategory;

new class extends Component {
    public $pinnedProjects = [];
    public $recentProjects = [];
    public $searchQuery = '';

    public function mount()
    {
        $this->loadProjects();
    }

    #[On('project-pinned-updated')]
    #[On('project-updated')]
    public function loadProjects()
    {
        $user = auth()->user();
        if ($user) {
            $this->pinnedProjects = $user->projects()
                ->where('is_pinned', true)
                ->whereNull('archived_at')
                ->whereHas('section', function($query) {
                    $query->whereNull('archived_at');
                })
                ->latest('updated_at')
                ->take(10)
                ->get();

            $this->recentProjects = $user->projects()
                ->whereNull('archived_at')
                ->whereHas('section', function($query) {
                    $query->whereNull('archived_at');
                })
                ->latest('updated_at')
                ->take(10)
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

    public function calculateRelevance($string, $query) {
        $string = (string) $string;
        $query = (string) $query;
        $pos = stripos($string, $query);
        if ($pos === false) return 999;
        if (strtolower(trim($string)) === strtolower(trim($query))) return 0;
        if ($pos === 0) return 1;
        if ($pos > 0 && substr($string, $pos - 1, 1) === ' ') return 2;
        return 3 + $pos;
    }

    public function highlight($text, $query, $class = 'font-bold text-text-100') {
        $text = (string) $text;
        $query = (string) $query;
        if (!$query) return htmlspecialchars($text);
        $escapedText = htmlspecialchars($text);
        $escapedQuery = htmlspecialchars($query);
        $q = preg_quote($escapedQuery, '/');
        return preg_replace('/(' . $q . ')/i', '<span class="' . $class . '">$1</span>', $escapedText);
    }

    #[Computed]
    public function searchResultsData()
    {
        if (trim($this->searchQuery) === '') {
            $this->searchResultsData = null;
            return;
        }

        $query = trim($this->searchQuery);
        $user = auth()->user();

        $results = [];

        $projects = $user->projects()
            ->where(function($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('synopsis', 'like', "%{$query}%");
            })
            ->whereNull('archived_at')
            ->take(5)
            ->get();

        foreach($projects as $p) {
            $url = route('projects.show', $p->project_id);
            if(!isset($results[$url])) {
                $results[$url] = [
                    'type' => 'project',
                    'title' => $p->title,
                    'badge_prefix' => 'title: ',
                    'badge_keyword' => $p->title,
                    'url' => $url,
                    'icon_type' => $p->icon_type,
                    'icon' => $p->icon,
                    'score' => $this->calculateRelevance($p->title, $query)
                ];
            }
        }

        $categories = \App\Models\ProjectCategory::whereHas('project', function($q) use ($user) {
                $q->where('user_id', $user->user_id)->whereNull('archived_at');
            })->where('name', 'like', "%{$query}%")->take(5)->get();

        foreach($categories as $c) {
            if (!$c->project) continue;
            $url = route('projects.show', $c->project_id);
            if(!isset($results[$url])) {
                $results[$url] = [
                    'type' => 'category',
                    'title' => $c->project->title,
                    'matched_categories' => [$c->name],
                    'url' => $url,
                    'icon_type' => $c->project->icon_type,
                    'icon' => $c->project->icon,
                    'score' => $this->calculateRelevance($c->name, $query)
                ];
            } else {
                $results[$url]['type'] = 'category';
                if (!isset($results[$url]['matched_categories'])) {
                    $results[$url]['matched_categories'] = [];
                }
                if (!in_array($c->name, $results[$url]['matched_categories'])) {
                    $results[$url]['matched_categories'][] = $c->name;
                }
                $results[$url]['score'] = min($results[$url]['score'], $this->calculateRelevance($c->name, $query));
            }
        }

        $characters = \App\Models\Character::whereHas('project', function($q) use ($user) {
                $q->where('user_id', $user->user_id)->whereNull('archived_at');
            })->where('full_name', 'like', "%{$query}%")->take(5)->get();

        foreach($characters as $c) {
            if (!$c->project) continue;
            $url = route('projects.show', $c->project_id);
            if(!isset($results[$url])) {
                $results[$url] = [
                    'type' => 'character',
                    'title' => $c->project->title,
                    'badge_prefix' => 'char: ',
                    'badge_keyword' => $c->full_name,
                    'url' => $url,
                    'score' => $this->calculateRelevance($c->full_name, $query)
                ];
            }
        }

        $notes = \App\Models\Note::whereHas('project', function($q) use ($user) {
                $q->where('user_id', $user->user_id)->whereNull('archived_at');
            })->where('title', 'like', "%{$query}%")->take(5)->get();

        foreach($notes as $n) {
            if (!$n->project) continue;
            $url = route('projects.show', $n->project_id);
            if(!isset($results[$url])) {
                $results[$url] = [
                    'type' => 'note',
                    'title' => $n->project->title,
                    'badge_prefix' => 'notes: ',
                    'badge_keyword' => $n->title,
                    'url' => $url,
                    'score' => $this->calculateRelevance($n->title, $query)
                ];
            }
        }

        $sections = $user->sections()
            ->where('title', 'like', "%{$query}%")
            ->whereNull('archived_at')
            ->take(5)
            ->get();

        foreach($sections as $s) {
            $url = route('dashboard') . '#section-' . $s->section_id;
            if(!isset($results[$url])) {
                $results[$url] = [
                    'type' => 'section',
                    'section_id' => $s->section_id,
                    'title' => 'Section: ' . $s->title,
                    'badge_prefix' => '',
                    'badge_keyword' => '',
                    'url' => $url,
                    'score' => $this->calculateRelevance($s->title, $query)
                ];
            }
        }

        $finalResults = array_values($results);
        usort($finalResults, function($a, $b) {
            if ($a['score'] == $b['score']) {
                return strcmp($a['title'], $b['title']);
            }
            return $a['score'] <=> $b['score'];
        });

        $finalResults = array_slice($finalResults, 0, 20);

        return [
            'items' => $finalResults,
            'total' => count($finalResults)
        ];
    }
}; ?>
<aside
    @mouseleave="$store.layout.isHovered = false"
    class="fixed inset-y-0 left-0 z-50 w-72 bg-brand-100 border-r border-brand-150 transition-transform duration-300 ease-in-out flex flex-col shadow-sm"
    :class="($store.layout.isPinned || $store.layout.isHovered) ? 'translate-x-0' : '-translate-x-full'"
>
    <button
        @click="$store.layout.togglePin(false); $store.layout.isHovered = false"
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
                {{ Auth::user()->profile?->username ?? 'User' }}
                </span>

                <span class="text-app-body-small text-subtext-90 truncate" title="{{ Auth::user()->email }}">
                {{ Auth::user()->email }}
                </span>
            </div>
        </div>
    </div>

    <div class="px-6 mb-8">
        <div class="relative">
            <svg wire:loading.remove wire:target="searchQuery" class="absolute left-3 top-2.5 w-4 h-4 text-subtext-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <svg wire:loading wire:target="searchQuery" class="absolute left-3 top-2.5 w-4 h-4 text-subtext-100 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <input wire:model.live.debounce.300ms="searchQuery" type="text" placeholder="{{ __('Search The Yarn') }}" class="w-full pl-9 pr-8 py-2 bg-brand-10 border border-brand-150 rounded-full text-app-body-medium text-text-80 placeholder-subtext-100 focus:ring-1 focus:ring-secondary-150 outline-none transition-shadow">
            
            <button x-show="$wire.searchQuery !== ''" @click="$wire.set('searchQuery', ''); $wire.searchResultsData = null" class="absolute right-3 top-2.5 text-subtext-100 hover:text-text-80 transition-colors" x-cloak>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    <div 
        class="flex flex-col flex-1 overflow-y-auto [scrollbar-gutter:stable] px-6 pb-6 custom-scrollbar"
    >
        @if($this->searchResultsData !== null)
            <div wire:key="search-results-block" wire:transition>
                <div class="space-y-4">
                    <div class="text-app-feature text-text-80 flex justify-between items-center">
                        <span>{{ __('Search Results') }}</span>
                        <span class="text-[10px] bg-brand-150 px-1.5 py-0.5 rounded text-secondary-250 font-medium">{{ $this->searchResultsData['total'] }} {{ __('found') }}</span>
                    </div>
                
                @if($this->searchResultsData['total'] === 0)
                    <div class="flex flex-col items-center justify-center py-4 text-center opacity-60">
                        <x-icons.sidebar-pen class="w-8 h-8 text-secondary-150 stroke-brand-100 dark:text-secondary-200 mb-1" />
                        <p class="text-app-desc-feature text-secondary-200">{{ __('No results found.') }}</p>
                    </div>
                @else
                    <div x-data="{ expandedSearch: false }" class="space-y-1 -mx-2 px-2 pb-2">
                        @foreach($this->searchResultsData['items'] as $index => $item)
                            @if($index === 5)
                                <div x-show="expandedSearch" x-collapse x-cloak class="space-y-1 mt-1">
                            @endif
                            @if($item['type'] === 'section')
                                <a href="{{ $item['url'] }}"
                                   @click.prevent="
                                       if (window.location.pathname === '{{ route('dashboard', [], false) }}' || window.location.pathname === '/') {
                                           document.getElementById('section-{{ $item['section_id'] }}')?.scrollIntoView({behavior: 'smooth'});
                                       } else {
                                           Livewire.navigate('{{ $item['url'] }}');
                                       }
                                   "
                                   class="flex items-center justify-between px-2 py-1.5 -mx-2 rounded-lg hover:bg-brand-150 transition-colors group cursor-pointer">
                            @else
                                <a href="{{ $item['url'] }}" wire:navigate class="flex items-center justify-between px-2 py-1.5 -mx-2 rounded-lg hover:bg-brand-150 transition-colors group cursor-pointer">
                            @endif
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    @if($item['type'] === 'project' || $item['type'] === 'category')
                                        @if(($item['icon_type'] ?? null) === 'emoji')
                                            <span class="text-[16px] leading-none shrink-0">{{ $item['icon'] }}</span>
                                        @elseif(($item['icon_type'] ?? null) === 'image' && !empty($item['icon']))
                                            <img src="{{ asset('storage/' . $item['icon']) }}" alt="" class="w-4 h-4 object-cover rounded shrink-0">
                                        @else
                                            <x-icons.sidebar-book class="w-4 h-4 text-secondary-150 shrink-0" />
                                        @endif
                                    @elseif($item['type'] === 'section')
                                        <x-icons.list class="w-4 h-4 text-secondary-250 shrink-0" />
                                    @endif
                                    <span class="text-[13px] font-medium text-text-80 truncate group-hover:text-text-100">{!! $this->highlight($item['title'], $searchQuery, 'font-bold text-text-100') !!}</span>
                                </div>
                                <span class="text-[10px] text-text-70 truncate ml-2 max-w-[130px] text-right">
                                    @if($item['type'] === 'category')
                                        <span class="italic">cat:</span> {!! $this->highlight(implode(', ', $item['matched_categories'] ?? []), $searchQuery, 'font-semibold text-text-100') !!}
                                    @endif
                                </span>
                            </a>
                            @if($loop->last && $index >= 5)
                                </div>
                            @endif
                        @endforeach

                        @if($this->searchResultsData['total'] > 5)
                            <button @click="expandedSearch = !expandedSearch" class="w-full text-center text-[11px] font-semibold text-secondary-250 hover:text-secondary-300 py-2 mt-2 transition-colors">
                                <span x-text="expandedSearch ? '{{ __('Show less') }}' : '{{ __('Show more') }} ({{ $this->searchResultsData['total'] - 5 }})'"></span>
                            </button>
                        @endif
                    </div>
                @endif
                </div>
                <div class="border-b border-brand-200 w-full my-6 shrink-0"></div>
            </div>
        @endif

        <div class="space-y-6 shrink-0">
            <div x-data="{ viewAll: false }">
            <div class="flex items-center justify-between w-full text-app-feature text-text-70 mb-2">
                <span>{{ __('Marked') }}</span>
            </div>
            <div>
                @if(count($pinnedProjects) > 0)
                    <div class="space-y-1 pb-2">
                        @foreach($pinnedProjects->take(3) as $pProject)
                            <div x-data="{ rowHovered: false }" @mouseenter="rowHovered = true" @mouseleave="rowHovered = false" wire:key="marked-{{ $pProject->project_id }}" class="group flex items-center justify-between px-2 py-1.5 -mx-2 rounded-lg hover:bg-brand-150 transition-colors">
                                <a href="{{ route('projects.show', $pProject->project_id) }}" wire:navigate class="flex items-center gap-2 flex-1 min-w-0">
                                    @if($pProject->icon_type === 'emoji')
                                        <span class="text-[16px] leading-none shrink-0">{{ $pProject->icon }}</span>
                                    @elseif($pProject->icon_type === 'image' && $pProject->icon)
                                        <img src="{{ asset('storage/' . $pProject->icon) }}" alt="" class="w-4 h-4 object-cover rounded shrink-0">
                                    @else
                                        <x-icons.sidebar-book class="w-4 h-4 text-text-70 shrink-0" />
                                    @endif
                                    <span class="text-[13px] font-medium text-text-80 truncate transition-colors" :class="rowHovered ? 'text-text-100' : ''">{{ $pProject->title }}</span>
                                </a>
                                <button wire:click="unpin('{{ $pProject->project_id }}')" class="transition-all p-1 shrink-0 text-secondary-100 hover:text-secondary-250 opacity-0 group-hover:opacity-100" :class="rowHovered ? 'opacity-100' : 'opacity-0'" title="{{ __('Unmark Project') }}">
                                    <x-icons.bookmark-slash class="w-4 h-4" />
                                </button>
                            </div>
                        @endforeach

                        @if(count($pinnedProjects) > 3)
                            <div x-show="viewAll" x-collapse x-cloak class="space-y-1 mt-1">
                                @foreach($pinnedProjects->skip(3) as $pProject)
                                    <div x-data="{ rowHovered: false }" @mouseenter="rowHovered = true" @mouseleave="rowHovered = false" wire:key="marked-more-{{ $pProject->project_id }}" class="group flex items-center justify-between px-2 py-1.5 -mx-2 rounded-lg hover:bg-brand-150 transition-colors">
                                        <a href="{{ route('projects.show', $pProject->project_id) }}" wire:navigate class="flex items-center gap-2 flex-1 min-w-0">
                                            @if($pProject->icon_type === 'emoji')
                                                <span class="text-[16px] leading-none shrink-0">{{ $pProject->icon }}</span>
                                            @elseif($pProject->icon_type === 'image' && $pProject->icon)
                                                <img src="{{ asset('storage/' . $pProject->icon) }}" alt="" class="w-4 h-4 object-cover rounded shrink-0">
                                            @else
                                                <x-icons.sidebar-book class="w-4 h-4 text-text-70 shrink-0" />
                                            @endif
                                            <span class="text-[13px] font-medium text-text-80 truncate transition-colors" :class="rowHovered ? 'text-text-100' : ''">{{ $pProject->title }}</span>
                                        </a>
                                        <button wire:click="unpin('{{ $pProject->project_id }}')" class="transition-all p-1 shrink-0 text-secondary-100 hover:text-secondary-250 opacity-0 group-hover:opacity-100" :class="rowHovered ? 'opacity-100' : 'opacity-0'" title="{{ __('Unmark Project') }}">
                                            <x-icons.bookmark-slash class="w-4 h-4" />
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                            <button @click="viewAll = !viewAll" class="w-full text-center text-[11px] font-semibold text-secondary-250 hover:text-secondary-300 py-1.5 transition-colors">
                                <span x-text="viewAll ? '{{ __('Show less') }}' : '{{ __('View all') }} ({{ count($pinnedProjects) }})'"></span>
                            </button>
                        @endif
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-4 text-center opacity-60">
                        <x-icons.sidebar-pen class="w-8 h-8 text-secondary-150 stroke-brand-100 dark:text-secondary-200 mb-1" />
                        <p class="text-app-desc-feature text-secondary-200">{{ __('No Marked Projects!') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div x-data="{ viewAll: false }">
            <div class="flex items-center justify-between w-full text-app-feature text-text-70 mb-2">
                <span>{{ __('Recent') }}</span>
            </div>
            <div>
                @if(count($recentProjects) > 0)
                    <div class="space-y-1 pb-2">
                        @foreach($recentProjects->take(3) as $rProject)
                            <a href="{{ route('projects.show', $rProject->project_id) }}" wire:navigate class="flex items-center gap-2 px-2 py-1.5 -mx-2 rounded-lg hover:bg-brand-150 transition-colors group">
                                @if($rProject->icon_type === 'emoji')
                                    <span class="text-[16px] leading-none shrink-0">{{ $rProject->icon }}</span>
                                @elseif($rProject->icon_type === 'image' && $rProject->icon)
                                    <img src="{{ asset('storage/' . $rProject->icon) }}" alt="" class="w-4 h-4 object-cover rounded shrink-0">
                                @else
                                    <x-icons.sidebar-book class="w-4 h-4 text-text-70 shrink-0" />
                                @endif
                                <span class="text-[13px] font-medium text-text-80 truncate group-hover:text-text-100 transition-colors">{{ $rProject->title }}</span>
                            </a>
                        @endforeach
                        
                        @if(count($recentProjects) > 3)
                            <div x-show="viewAll" x-collapse x-cloak class="space-y-1 mt-1">
                                @foreach($recentProjects->skip(3) as $rProject)
                                    <a href="{{ route('projects.show', $rProject->project_id) }}" wire:navigate class="flex items-center gap-2 px-2 py-1.5 -mx-2 rounded-lg hover:bg-brand-150 transition-colors group">
                                        @if($rProject->icon_type === 'emoji')
                                            <span class="text-[16px] leading-none shrink-0">{{ $rProject->icon }}</span>
                                        @elseif($rProject->icon_type === 'image' && $rProject->icon)
                                            <img src="{{ asset('storage/' . $rProject->icon) }}" alt="" class="w-4 h-4 object-cover rounded shrink-0">
                                        @else
                                            <x-icons.sidebar-book class="w-4 h-4 text-text-70 shrink-0" />
                                        @endif
                                        <span class="text-[13px] font-medium text-text-80 truncate group-hover:text-text-100 transition-colors">{{ $rProject->title }}</span>
                                    </a>
                                @endforeach
                            </div>
                            <button @click="viewAll = !viewAll" class="w-full text-center text-[11px] font-semibold text-secondary-250 hover:text-secondary-300 py-1.5 transition-colors">
                                <span x-text="viewAll ? '{{ __('Show less') }}' : '{{ __('View all') }} ({{ count($recentProjects) }})'"></span>
                            </button>
                        @endif
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-4 text-center opacity-60">
                        <x-icons.sidebar-pen class="w-8 h-8 text-secondary-150 stroke-brand-100 dark:text-secondary-200 mb-1" />
                        <p class="text-app-desc-feature text-secondary-200">{{ __('You Didn\'t Have Any Project!') }}</p>
                    </div>
                @endif
            </div>
        </div>

        </div>
        
        <div class="pt-6 shrink-0">
            <div class="text-app-feature text-text-70 mb-2">{{ __('Others') }}</div>
            <div class="space-y-1">
                <a href="{{ route('archive') }}" wire:navigate class="flex items-center px-3 py-2 -mx-3 rounded-lg text-app-body-medium text-text-80 hover:bg-brand-150 hover:text-text-100 transition-colors group {{ request()->routeIs('archive') ? 'bg-brand-150 text-text-100' : '' }}">
                    <x-icons.archive class="w-5 h-5 mr-3 text-text-80 group-hover:text-text-100 transition-colors" />
                    {{ __('Archive') }}
                </a>

                <a href="{{ route('settings') }}" wire:navigate class="flex items-center px-3 py-2 -mx-3 rounded-lg text-app-body-medium text-text-80 hover:bg-brand-150 hover:text-text-100 transition-colors group {{ request()->routeIs('settings') ? 'bg-brand-150 text-text-100' : '' }}">
                    <x-icons.setting class="w-5 h-5 mr-3 text-text-80 group-hover:text-text-100 transition-colors" />
                    {{ __('Settings') }}
                </a>
            </div>
            </div>
            </div>
        </div>
    </div>
</aside>
