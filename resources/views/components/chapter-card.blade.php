@props(['chapter', 'sections' => []])

<div 
    x-data="{ open: false }" 
    @mouseleave="open = false"
    class="flex flex-col w-full group cursor-grab active:cursor-grabbing"
>
    
    <div class="flex justify-between items-end mb-1.5 px-1">
        
        <div class="text-app-desc-feature text-text-60 group-hover:text-secondary-200 transition-colors">
            {{ __('Chapter') }} {{ $chapter->order_index }}
        </div>
        
        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            
            <div class="relative" @click.outside="open = false">
                <button 
                    @click.stop="open = !open"
                    :class="open ? 'bg-secondary-200/10 text-secondary-200' : 'text-text-60 hover:text-secondary-200 hover:bg-secondary-200/10'"
                    class="p-1 rounded transition-all focus:outline-none"
                    title="{{ __('Move to another section') }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                </button>

                <div 
                    x-show="open" 
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                    class="absolute right-0 top-full mt-2 w-56 origin-top-right bg-card-bg border border-card-border rounded-lg shadow-xl z-50 flex flex-col max-h-52 overflow-y-auto custom-scrollbar"
                    style="display: none;"
                >
                    <div class="sticky top-0 bg-card-bg/95 backdrop-blur-sm px-3 py-2 border-b border-card-border z-10">
                        <span class="text-app-desc-feature font-bold uppercase text-text-40 tracking-wider">
                            {{ __('Move to Section') }}
                        </span>
                    </div>
                    
                    <div class="py-1">
                        @foreach($sections as $section)
                            @php
                                $isCurrentSection = $section->structure_section_id === $chapter->structure_section_id;
                            @endphp
                            
                            <button 
                                @if(!$isCurrentSection)
                                    @click.stop="$dispatch('move-chapter', { chapterId: '{{ $chapter->chapter_card_id }}', targetSectionId: '{{ $section->structure_section_id }}' }); open = false"
                                @endif
                                @class([
                                    'w-full text-left px-3 py-2 text-app-desc-feature transition-colors flex items-center justify-between group/item',
                                    'text-text-40 cursor-not-allowed bg-brand-50/30' => $isCurrentSection,
                                    'text-text-80 hover:bg-brand-100 hover:text-secondary-200' => !$isCurrentSection,
                                ])
                                @if($isCurrentSection) disabled @endif
                            >
                                <span class="truncate pr-2">{{ $section->title }}</span>
                                
                                @if($isCurrentSection)
                                    <span class="text-[9px] uppercase font-bold text-text-50 bg-card-border px-1.5 py-0.5 rounded-sm shrink-0">
                                        {{ __('Current') }}
                                    </span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <button 
                @click.stop="$dispatch('open-delete-dialog', { id: '{{ $chapter->chapter_card_id }}' })"
                class="text-text-60 hover:text-danger-100 hover:bg-danger-100/10 px-1 pb-1 rounded transition-all focus:outline-none"
                title="{{ __('Delete Chapter') }}"
            >
                <x-icons.delete class="w-4 h-4" />            
            </button>
            
        </div>
        
    </div>
    
    <a href="{{ route('projects.manuscript', ['project' => $chapter->project_id, 'chapterCard' => $chapter->chapter_card_id]) }}" wire:navigate 
        class="cursor-grab active:cursor-grabbing bg-card-bg border border-card-border rounded-lg shadow-md flex flex-col flex-col-1 overflow-hidden transition-all duration-300 group-hover:border-secondary-200 dark:group-hover:border-secondary-150 group-hover:shadow-xl group-hover:-translate-y-1 min-h-[230px]">
        
        <div class="p-5 flex-1 flex flex-col">
            
            <div class="flex justify-between items-baseline border-b-1 border-card-border pb-2 mb-3">
                <h3 class="text-app-body-large truncate pr-4 group-hover:text-secondary-200 transition-colors">
                    {{ $chapter->title }}
                </h3>
            </div>
            
            <p class="text-app-body-small text-text-90 line-clamp-5">
                {{ $chapter->summary ?? __('No Description About This Chapter!') }}
            </p>
        </div>

        <div class="bg-brand-100 dark:bg-brand-150 px-2 py-3 flex flex-col gap-2 transition-colors">
            
            <div class="flex items-center gap-1 overflow-hidden">
                @if($chapter->tags->isNotEmpty())
                    @foreach($chapter->tags->take(2) as $tag)
                        <span class="text-app-caption text-secondary-100 bg-card-hover border border-brand-200 px-2 py-0.5 rounded truncate max-w-[80px]">
                            {{ $tag->name }}
                        </span>
                    @endforeach

                    @if($chapter->tags->count() > 2)
                        <span class="text-app-caption text-secondary-100 px-1">
                            +{{ $chapter->tags->count() - 2 }} {{ __('more') }}
                        </span>
                    @endif
                @else
                    <span class="text-app-caption text-secondary-100 bg-card-hover border border-dashed border-brand-200 px-2 py-0.5 rounded italic">
                        {{ __('No tag here') }}
                    </span>
                @endif
            </div>

            <div class="flex justify-between items-center">
                
                <span class="text-app-body-small text-text-60">
                    {{ $chapter->manuscript ? number_format($chapter->manuscript->count()) : 0 }} {{ __('Draft(s)') }}
                </span>
                
                <span @class([
                    'text-app-caption text-text-80 px-2 py-1 rounded-md flex items-center gap-1.5 shadow-sm',
                    'bg-warning-100/50' => $chapter->status === 'In Progress',
                    'bg-success-100/50' => $chapter->status === 'Completed',
                    'bg-brand-150' => !in_array($chapter->status, ['In Progress', 'Completed'])
                ])>
                    <x-icons.chapter-status :status="$chapter->status" />
                    {{ __($chapter->status ?? 'In Progress') }}
                </span>
            </div>
        </div>

    </a>
</div>
