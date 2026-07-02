<div {{ $attributes->merge(['class' => 'bg-gradient-to-br from-[#C1AE8E] to-[#977E5C] relative overflow-hidden']) }}>
    <!-- Inner Border -->
    <div class="absolute inset-[6px] border-[1.5px] border-[#E8E3D9]/50 rounded-[3px] pointer-events-none z-10"></div>
    
    <!-- Top Left Ornament -->
    <div class="absolute top-[6px] left-[6px] w-[30px] h-[30px] pointer-events-none z-20">
        <!-- Diagonal Line -->
        <div class="absolute top-[14px] -left-[6px] w-[42px] h-[1.5px] bg-[#E8E3D9]/50 -rotate-45 origin-center"></div>
        <!-- Little Leaf/Diamond -->
        <div class="absolute top-[4px] left-[4px] w-[10px] h-[10px]">
            <svg viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full text-[#E8E3D9]/60">
                <path d="M5 1L9 5L5 9L1 5L5 1Z" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/>
                <path d="M5 5L2.5 2.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
            </svg>
        </div>
    </div>

    <!-- Bottom Right Ornament -->
    <div class="absolute bottom-[6px] right-[6px] w-[30px] h-[30px] pointer-events-none z-20">
        <!-- Diagonal Line -->
        <div class="absolute bottom-[14px] -right-[6px] w-[42px] h-[1.5px] bg-[#E8E3D9]/50 -rotate-45 origin-center"></div>
        <!-- Little Leaf/Diamond -->
        <div class="absolute bottom-[4px] right-[4px] w-[10px] h-[10px]">
            <svg viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full text-[#E8E3D9]/60">
                <path d="M5 1L9 5L5 9L1 5L5 1Z" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/>
                <path d="M5 5L7.5 7.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
            </svg>
        </div>
    </div>
</div>
