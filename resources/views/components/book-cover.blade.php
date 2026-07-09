@props([
    'imagePath' => null
])

<div {{ $attributes->merge(['class' => 'relative w-full h-full']) }}>
    @if($imagePath)
        <!-- Image / Front Cover -->
        <div class="absolute top-0 left-0 w-[calc(100%-16px)] h-full z-20 rounded-l-md rounded-r-xl overflow-hidden shadow-md bg-gradient-to-br from-[#C1AE8E] to-[#977E5C] p-[10px]">
            <div class="w-full h-full overflow-hidden rounded-sm bg-brand-100">
                <img src="{{ Storage::url($imagePath) }}" class="w-full h-full object-cover" />
            </div>
        </div>
    @else
        <x-default-project class="absolute top-0 left-0 w-[calc(100%-16px)] h-full text-[#B69F78] rounded-l-md rounded-r-xl shadow-md z-20 border-r border-black/10" />
    @endif

    <!-- Book pages on the right -->
    <div class="absolute top-3.5 bottom-3.5 right-2 w-4 bg-gradient-to-r from-[#E8E3D9] to-[#D5C6A9] border-y border-r border-[#C4B7A3] rounded-r-sm z-10 shadow-inner"></div>
    <!-- Back cover sticking out -->
    <div class="absolute inset-y-0 right-0 w-8 bg-[#8C7558] rounded-r-xl z-0 shadow-xl border-l border-black/20"></div>
</div>
