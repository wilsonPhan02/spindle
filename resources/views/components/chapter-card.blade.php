@props(['chapter'])

<div class="flex flex-col w-full group cursor-pointer">
    
    <div class="text-[13px] text-text-60 font-medium mb-1.5 ml-1 group-hover:text-secondary-200 transition-colors">
        Chapter {{ $chapter->order_index }}
    </div>
    
    <div class="bg-white border border-[#E8E1D5] rounded-xl shadow-sm flex flex-col flex-1 overflow-hidden transition-all group-hover:border-secondary-200 group-hover:shadow-md min-h-[260px]">
        
        <div class="p-5 flex-1 flex flex-col">
            
            <div class="flex justify-between items-baseline border-b-2 border-[#F0EBE1] pb-2 mb-3">
                <h3 class="text-app-heading-2 text-text-90 font-semibold truncate pr-4">
                    {{ $chapter->title }}
                </h3>
            </div>
            
            <p class="text-[14px] text-text-80 line-clamp-4 leading-relaxed font-medium">
                {{ $chapter->summary ?? 'No Description About This Chapter!' }}
            </p>
        </div>

        <div class="bg-[#EFECE5] px-5 py-4 flex flex-col gap-4 border-t border-[#E8E1D5]">
            
            <div class="flex">
                <span class="text-[11px] text-text-60 font-medium bg-white border border-[#DCD6CC] px-2 py-1 rounded shadow-sm">
                    No Tag Here
                </span>
            </div>

            <div class="flex justify-between items-center">
                
                <span class="text-[13px] text-text-70 font-semibold">
                    {{ $chapter->manuscript ? number_format($chapter->manuscript->count()) : 0 }} Draft(s)
                </span>
                
                <span @class([
                    'text-[11px] px-2.5 py-1.5 rounded-md flex items-center gap-1.5 font-bold tracking-wide shadow-sm',
                    'bg-[#F8D664] text-[#5A4114]' => $chapter->status === 'In Progress',
                    'bg-[#CEEAD6] text-[#0D532A]' => $chapter->status === 'Completed',
                    'bg-[#D2E3FC] text-[#174EA6]' => $chapter->status === 'Draft',
                    'bg-[#F1F3F4] text-[#5F6368]' => !in_array($chapter->status, ['In Progress', 'Completed', 'Draft'])
                ])>
                    @if($chapter->status === 'In Progress')
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    @elseif($chapter->status === 'Completed')
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    @else
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    @endif
                    {{ $chapter->status ?? 'In Progress' }}
                </span>
            </div>
        </div>

    </div>
</div>