@props(['chapter'])

<div class="flex flex-col w-full group cursor-pointer">
    
    <div class="flex justify-between items-end mb-1.5 px-1">
        
        <div class="text-app-desc-feature text-text-60 group-hover:text-secondary-200 transition-colors">
            Chapter {{ $chapter->order_index }}
        </div>
        
        <button 
            @click.stop="$dispatch('open-delete-dialog', { id: '{{ $chapter->chapter_card_id }}' })"
            class="text-text-60 hover:text-danger-100 hover:bg-danger-100/10 p-1 rounded transition-all opacity-0 group-hover:opacity-100 focus:outline-none"
            title="Delete Chapter"
        >
            <x-icons.delete-default size="w-4 h-4" color="currentColor"/>            
        </button>
        
    </div>
    
    <div class="bg-card-bg border border-card-border rounded-lg shadow-md flex flex-col flex-1 overflow-hidden transition-all group-hover:border-secondary-200 group-hover:shadow-lg group-hover:bg-card-hover min-h-[260px]">
        
        <div class="p-5 flex-1 flex flex-col">
            
            <div class="flex justify-between items-baseline border-b-1 border-card-border pb-2 mb-3">
                <h3 class="text-app-body-large truncate pr-4">
                    {{ $chapter->title }}
                </h3>
            </div>
            
            <p class="text-app-body-small text-text-90 line-clamp-6">
                {{ $chapter->summary ?? 'No Description About This Chapter!' }}
            </p>
        </div>

        <div class="bg-brand-100 px-5 py-4 flex flex-col gap-2 group-hover:bg-brand-150">
            
            <div class="flex items-center gap-2 overflow-hidden">
                @if($chapter->tags->isNotEmpty())
                    @foreach($chapter->tags->take(2) as $tag)
                        <span class="text-app-desc-feature text-secondary-100 bg-brand-100 border border-brand-200 px-2 py-0.5 rounded truncate max-w-[80px]">
                            {{ $tag->name }}
                        </span>
                    @endforeach

                    @if($chapter->tags->count() > 2)
                        <span class="text-app-desc-feature text-secondary-100 px-1">
                            +{{ $chapter->tags->count() - 2 }} more
                        </span>
                    @endif
                @else
                    <span class="text-app-desc-feature text-secondary-100 bg-card-hover border border-dashed border-brand-200 px-2 py-0.5 rounded italic">
                        No tag here
                    </span>
                @endif
            </div>

            <div class="flex justify-between items-center">
                
                <span class="text-app-body-small text-text-60">
                    {{ $chapter->manuscript ? number_format($chapter->manuscript->count()) : 0 }} Draft(s)
                </span>
                
                <span @class([
                    'text-app-desc-feature text-text-80 px-2.5 py-1.5 rounded-md flex items-center gap-1.5 shadow-sm',
                    'bg-warning-100/70' => $chapter->status === 'In Progress',
                    'bg-success-100/70' => $chapter->status === 'Completed',
                    'bg-text-100' => !in_array($chapter->status, ['In Progress', 'Completed'])
                ])>
                    <x-icons.chapter-status :status="$chapter->status" />
                    {{ $chapter->status ?? 'In Progress' }}
                </span>
            </div>
        </div>

    </div>
</div>