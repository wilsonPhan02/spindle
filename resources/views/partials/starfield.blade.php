{{-- Taburan bintang berkelap-kelip untuk section bertema gelap. --}}
@php
    // Posisi (top%, left%, ukuran px, delay s) ditentukan agar konsisten tiap render.
    $stars = [
        [8, 12, 2, 0.0], [16, 78, 3, 1.1], [24, 34, 2, 2.0], [12, 56, 2, 0.6],
        [38, 88, 3, 1.7], [46, 18, 2, 0.3], [55, 64, 2, 2.4], [62, 40, 3, 1.0],
        [70, 84, 2, 0.8], [78, 24, 2, 2.2], [86, 60, 3, 1.4], [33, 6, 2, 0.5],
        [52, 92, 2, 1.9], [90, 14, 2, 0.2], [20, 50, 2, 1.3], [68, 8, 3, 2.6],
    ];
@endphp
<div class="pointer-events-none absolute inset-0 z-0 overflow-hidden">
    @foreach ($stars as $s)
        <span class="animate-twinkle absolute rounded-full bg-brand-10"
              style="top: {{ $s[0] }}%; left: {{ $s[1] }}%; height: {{ $s[2] }}px; width: {{ $s[2] }}px; animation-delay: {{ $s[3] }}s;"></span>
    @endforeach
</div>
