<div class="lg:hidden fixed inset-0 z-[99999] bg-[#2a1f17] flex flex-col items-center justify-center p-8 text-center overflow-hidden">
    
    {{-- Decorative Starfield to match landing page --}}
    @include('partials.starfield')
    
    <div class="relative z-10 w-full max-w-sm flex flex-col items-center gap-8 animate-fade-in">
        
        {{-- Spindle Logo --}}
        <x-logo class="h-10 w-auto select-none text-[#F3ECE3] mb-2 drop-shadow-md" />
        
        {{-- Desktop Icon Graphic --}}
        <div class="relative flex items-center justify-center w-28 h-28 rounded-full bg-[#372A1F] border border-[#554C46] shadow-[0_20px_40px_rgba(0,0,0,0.4)]">
            <svg class="w-12 h-12 text-[#CAB79B]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            <div class="absolute -bottom-1 -right-1 bg-red-500 text-white rounded-full p-2 border-4 border-[#2a1f17]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
        </div>
        
        {{-- Typography matching landing page --}}
        <div>
            <h2 class="text-[28px] font-bold font-merriweather italic text-[#F3ECE3] mb-4 tracking-tight drop-shadow-md">
                {{ __('Desktop Required') }}
            </h2>
            <p class="text-[16px] leading-[26px] font-montserrat text-[#E3DBD0] opacity-90">
                {{ __('Spindle is a complex writing environment optimized for larger screens. To weave your story and access all features, please open this application on a desktop or laptop device.') }}
            </p>
        </div>
    </div>
    
    {{-- Mountain Footer matching landing page --}}
    <div class="pointer-events-none absolute inset-x-0 bottom-[-5%] z-[5] w-[140%] left-[-20%] opacity-80">
        <img src="{{ asset('images/landing/mountains-dark.png') }}" class="w-full max-w-none" alt="">
    </div>
    
    {{-- Gradient Overlay at bottom to blend mountains --}}
    <div class="pointer-events-none absolute inset-x-0 bottom-0 z-[6] h-[20%] bg-gradient-to-t from-[#2a1f17] to-transparent"></div>
</div>
