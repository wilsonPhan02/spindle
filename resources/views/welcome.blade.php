<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Spindle — Spin A Yarn</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

@php $img = fn ($f) => asset('images/landing/' . $f); @endphp

<body class="bg-brand-50 text-text-80 font-montserrat antialiased overflow-x-hidden">

    {{-- ===================== NAVBAR (pill putih mengambang) ===================== --}}
    <nav class="fixed top-5 inset-x-0 z-50">
        <div class="mx-auto max-w-[1320px] px-8 lg:px-16">
            <div class="flex items-center justify-between rounded-full bg-white py-3 pl-10 pr-3 shadow-[0_10px_34px_rgba(120,100,80,0.16)]">
                <a href="#hero" class="flex items-center">
                    <img src="{{ $img('logo-spindle.png') }}" alt="Spindle" class="h-[26px] w-auto select-none">
                </a>
                <ul class="hidden md:flex items-center gap-9 font-montserrat text-[16px] text-text-80">
                    <li><a href="#hero"    class="nav-link hover:text-secondary-200 transition-colors">Background</a></li>
                    <li><a href="#about"   class="nav-link hover:text-secondary-200 transition-colors">About Us</a></li>
                    <li><a href="#tools"   class="nav-link hover:text-secondary-200 transition-colors">Guides</a></li>
                    <li><a href="#writers" class="nav-link hover:text-secondary-200 transition-colors">Advantages</a></li>
                </ul>
                <a href="{{ route('register') }}"
                   class="inline-flex items-center rounded-[10px] bg-secondary-150 px-7 py-3 font-merriweather text-[15px] text-brand-10
                          transition-all duration-300 hover:bg-secondary-200 hover:-translate-y-0.5 hover:shadow-md">
                    Join Us Now
                </a>
            </div>
        </div>
    </nav>

    {{-- ===================== HERO ===================== --}}
    <header id="hero" class="relative min-h-[780px] overflow-hidden">
        {{-- langit hangat: tan di atas (biar awan kontras) → krem di bawah --}}
        <div class="absolute inset-0 z-0 bg-gradient-to-b from-[#cdb99e] via-[#e4d7c3] to-[#efe6d7]"></div>

        {{-- awan menyapu: gumpalan lembut ber-falloff radial (bukan balok) --}}
        @php
            // [top%, left%, lebar px, tinggi px, opacity, durasi s] — di langit terbuka (kiri-tengah)
            $clouds = [
                [8, 2, 300, 80, 0.7, 42], [15, 20, 250, 66, 0.55, 55],
                [9, 40, 300, 78, 0.6, 48], [21, 8, 210, 58, 0.5, 64],
                [14, 33, 190, 54, 0.45, 70], [6, 58, 240, 62, 0.4, 76],
            ];
        @endphp
        <div class="pointer-events-none absolute inset-0 z-[1] overflow-hidden">
            @foreach ($clouds as $i => $c)
                <div class="{{ $i % 2 ? 'animate-cloud-2' : 'animate-cloud' }} absolute rounded-full blur-2xl"
                     style="top: {{ $c[0] }}%; left: {{ $c[1] }}%; width: {{ $c[2] }}px; height: {{ $c[3] }}px;
                            animation-duration: {{ $c[5] }}s;
                            background: radial-gradient(ellipse at center, rgba(255,255,255,{{ $c[4] }}) 0%, rgba(255,255,255,{{ $c[4] * 0.5 }}) 45%, rgba(255,255,255,0) 72%);"></div>
            @endforeach
        </div>

        {{-- Lapisan gunung (transparan) — bleed melewati tepi kanan biar tidak kepotong --}}
        {{-- puncak ke-2 di belakang (Group 56) --}}
        <img src="{{ $img('mtn-peak2.png') }}" alt="" class="pointer-events-none absolute z-[1] max-w-none select-none"
             style="top: 21%; left: -8%; width: 116%;">
        {{-- gunung utama di depan (Group 55) --}}
        <img src="{{ $img('mtn-range.png') }}" alt="" class="pointer-events-none absolute z-[2] max-w-none select-none"
             style="top: 26.6%; left: -8%; width: 116%;">

        {{-- weaver figure (Group 3): duduk di gunung, ~66% tinggi hero --}}
        <img src="{{ $img('hero-figure.png') }}" alt="The Weaver"
             class="animate-sway pointer-events-none absolute z-[3] max-w-none select-none drop-shadow-[0_14px_22px_rgba(43,31,23,0.35)]"
             style="top: 36%; left: 59%; width: 37.6%;">

        {{-- pengikat: gunung bawah menggelap perlahan menyatu ke foreground gelap --}}
        <div class="pointer-events-none absolute inset-x-0 bottom-0 z-[3] h-[30%] bg-gradient-to-b from-transparent via-[#3a2a1c]/30 to-[#2a1f17]"></div>

        {{-- foreground: siluet gunung lancip gelap (Rectangle 442), diperbesar biar naik & menyambung --}}
        <img src="{{ $img('mountains-dark.png') }}" alt=""
             class="pointer-events-none absolute inset-x-[-22%] bottom-[-1px] z-[4] w-[144%] max-w-none select-none">

        {{-- copy --}}
        <div class="relative z-20 mx-auto max-w-[1240px] px-6 lg:px-[52px] pt-[176px]">
            <div class="max-w-[560px]">
                <h1 class="reveal text-web-title text-text-80">
                    Are You Ready To<br>
                    <span class="font-merriweather italic text-secondary-200">Spin A Yarn?</span>
                </h1>
                <p class="reveal reveal-d1 mt-6 max-w-[500px] font-montserrat text-[18px] leading-[28px] text-text-70">
                    Weave every character, plot, and world into a story worth telling, spinning
                    countless narrative threads into one cohesive universe.
                </p>
                <a href="{{ route('register') }}"
                   class="reveal reveal-d2 mt-8 inline-flex items-center rounded-[7px] bg-secondary-150 px-10 py-3.5 font-merriweather text-[16px] text-brand-10
                          shadow-md transition-all duration-300 hover:bg-secondary-200 hover:-translate-y-0.5 hover:shadow-lg">
                    Join Us Now
                </a>
            </div>
        </div>
    </header>

    {{-- ===================== THE CREATIVE REALISM ===================== --}}
    <section class="relative overflow-hidden bg-[#2a1f17]">
        {{-- galaksi (spiral saja, tanpa teks) — nyaris diam, hanya berdenyut halus, penuh terlihat --}}
        <div class="pointer-events-none absolute right-[4%] top-1/2 z-0 h-[520px] w-[520px] max-w-[86%] -translate-y-1/2">
            <img src="{{ $img('galaxy-spiral.png') }}" alt=""
                 class="animate-spin-slow h-full w-full object-contain opacity-80" style="animation-duration: 220s;">
        </div>
        @include('partials.starfield')
        <div class="relative z-10 mx-auto max-w-[1240px] px-6 lg:px-[52px] py-32">
            <h2 class="reveal text-web-title leading-tight text-brand-10">The Creative<br>Realism</h2>
            <p class="reveal reveal-d1 mt-6 max-w-[520px] font-montserrat text-[18px] leading-[28px] text-brand-200">
                In a Universe known as <span class="font-merriweather italic text-secondary-50">“The Creative Realms”</span>
                Lived the Writer of Worlds. They possess the power to create life from the void.
            </p>
        </div>
    </section>

    {{-- ===================== THE GREAT TANGLE ===================== --}}
    <section class="relative overflow-hidden bg-[#2a1f17] pb-28 pt-8">
        @include('partials.starfield')
        <div class="relative z-10 mx-auto max-w-[1240px] px-6 lg:px-[52px] text-center">
            <p class="reveal font-montserrat text-[18px] text-brand-200">However, every Writer faces the same monster</p>
            <h2 class="reveal reveal-d1 mt-2 font-merriweather text-[44px] font-bold italic text-brand-10">“The Great Tangle”</h2>

            <div class="mt-14 flex flex-wrap items-center justify-center gap-6">
                @foreach (['tangle-1.png' => 'rotate-[-5deg]', 'tangle-2.png' => 'lg:-translate-y-4 z-10', 'tangle-3.png' => 'rotate-[5deg]'] as $file => $tilt)
                    <img src="{{ $img($file) }}" alt="The Great Tangle"
                         class="reveal reveal-d{{ $loop->iteration }} animate-float w-[300px] max-w-[80%] {{ $tilt }} drop-shadow-[0_24px_40px_rgba(0,0,0,0.45)]"
                         style="animation-delay: {{ $loop->index * 1.1 }}s">
                @endforeach
            </div>
        </div>
        {{-- fade menyatu ke section terang berikutnya --}}
        <div class="pointer-events-none absolute inset-x-0 bottom-0 z-0 h-44 bg-gradient-to-b from-transparent to-brand-50"></div>
    </section>

    {{-- ===================== BUT SPINDLE COME AS SOLUTION ===================== --}}
    <section id="about" class="relative overflow-hidden bg-brand-50 pt-10 pb-28">
        <div class="mx-auto grid max-w-[1240px] grid-cols-1 items-center gap-12 px-6 lg:px-[52px] lg:grid-cols-2">
            {{-- tangan menyodorkan kartu The Spindle (kartu berdiri di atas telapak) --}}
            <div class="reveal reveal-left relative order-2 mx-auto h-[420px] w-full max-w-[440px] overflow-hidden lg:order-1">
                {{-- tangan (siluet emas transparan, telapak menghadap atas) --}}
                <img src="{{ $img('hand.png') }}" alt=""
                     class="pointer-events-none absolute bottom-[-96px] left-1/2 z-0 w-[330px] -translate-x-1/2 select-none">
                {{-- kartu berdiri di atas jari, mengambang halus --}}
                <img src="{{ $img('group47.png') }}" alt="The Spindle"
                     class="animate-float absolute bottom-[150px] left-1/2 z-10 w-[170px] -translate-x-[52%] rotate-[-3deg] drop-shadow-[0_22px_30px_rgba(0,0,0,0.28)]">
            </div>

            <div class="reveal reveal-right order-1 lg:order-2">
                <p class="font-montserrat text-[18px] font-semibold text-secondary-200">WHO WE ARE?</p>
                <h2 class="mt-1 text-web-title text-text-80">But Spindle Come as Solution</h2>
                <p class="mt-6 max-w-[500px] font-montserrat text-[18px] leading-[28px] text-text-70">
                    We are going to help you create your story. Weave stories, characters, and notes
                    easily by using Spindle. Convert your abstract yarn into a magic yarn!
                </p>
            </div>
        </div>
    </section>

    {{-- ===================== FROM WRITERS TO WRITERS ===================== --}}
    <section id="writers" class="relative overflow-hidden bg-brand-50 py-24">
        <div class="mx-auto max-w-[1240px] px-6 lg:px-[52px] text-center">
            <p class="reveal font-montserrat text-[18px] font-semibold text-secondary-200">OUR MISSION</p>
            <h2 class="reveal reveal-d1 mt-1 text-web-title text-text-80">
                From Writers To <span class="font-merriweather italic text-secondary-200">Writers</span>
            </h2>
            <p class="reveal reveal-d2 mx-auto mt-4 max-w-[560px] font-montserrat text-[18px] leading-[28px] text-text-70">
                We are going to help you create your story. Weave stories, characters, and notes
                easily by using Spindle. Convert your abstract yarn into a magic yarn!
            </p>

            <div class="mt-14 flex flex-wrap items-center justify-center gap-6">
                <img src="{{ $img('writers-left.png') }}"  alt="" class="reveal reveal-d1 w-[240px] max-w-[80%] rotate-[-3deg] rounded-xl shadow-xl">
                <img src="{{ $img('writers-center.png') }}" alt="" class="reveal reveal-d2 z-10 w-[420px] max-w-[92%] rounded-xl shadow-2xl lg:-translate-y-4">
                <img src="{{ $img('writers-right.png') }}" alt="" class="reveal reveal-d3 w-[240px] max-w-[80%] rotate-[3deg] rounded-xl shadow-xl">
            </div>
        </div>
    </section>

    {{-- ===================== WE PROVIDE A TOOLS ===================== --}}
    <section id="tools" class="relative overflow-hidden bg-brand-50 pb-28">
        <div class="mx-auto max-w-[1240px] px-6 lg:px-[52px]">
            <p class="reveal font-montserrat text-[18px] font-semibold text-secondary-200">WHY CHOOSE US</p>
            <h2 class="reveal reveal-d1 mt-1 max-w-[599px] text-web-title text-text-80">We Provide A Tools For Writing</h2>

            <div class="mt-14 grid grid-cols-1 items-center gap-12 lg:grid-cols-2">
                {{-- illustration panel --}}
                <div class="reveal reveal-left order-2 lg:order-1">
                    <div class="flex aspect-[566/419] w-full items-center justify-center rounded-[21px] border-[7px] border-secondary-150 bg-brand-100">
                        <img src="{{ $img('group47.png') }}" alt="" class="animate-float w-40 opacity-90">
                    </div>
                </div>

                {{-- feature list --}}
                <div class="reveal reveal-right order-1 space-y-2 lg:order-2">
                    <div class="rounded-[5px] border-2 border-secondary-200 bg-brand-150 px-10 pt-7 pb-12">
                        <h3 class="font-merriweather text-[28px] italic text-secondary-200">Create a New Project</h3>
                        <p class="mt-2 font-montserrat text-[16px] leading-[24px] text-black">
                            Section merupakan sebuah folder untuk menyimpan seluruh series dari karya mu
                        </p>
                    </div>
                    @foreach (['Choose a Structure', 'Create Character Relationship', 'Add Your Notes'] as $feat)
                        <button class="flex w-full items-center rounded-[5px] border-2 border-brand-200 px-10 py-6 text-left
                                       transition-colors hover:border-secondary-150 hover:bg-brand-10">
                            <span class="font-merriweather text-[28px] italic text-secondary-100">{{ $feat }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== WEAVERS + FINAL CTA ===================== --}}
    <section class="relative overflow-hidden bg-brand-50 pt-16 pb-0">
        <div class="mx-auto max-w-[1240px] px-6 lg:px-[52px] text-center">
            <p class="reveal mx-auto max-w-[620px] font-merriweather text-[26px] italic leading-[40px] text-text-80">
                You’re are The <span class="text-secondary-200">“Weavers”</span>...
                The One Who Gonna Create A Yarn Into A Story
            </p>
        </div>

        {{-- Final CTA with books --}}
        <div class="reveal reveal-d1 relative mx-auto mt-14 max-w-[1240px] px-6 lg:px-[52px]">
            <div class="relative overflow-hidden rounded-t-[24px] bg-brand-100 px-6 pt-14 text-center">
                <h2 class="text-web-title text-text-80">
                    Are You Ready To <span class="font-merriweather italic text-secondary-200">Spin A Yarn?</span>
                </h2>
                <a href="{{ route('register') }}"
                   class="mt-7 inline-flex items-center rounded-[7px] bg-secondary-150 px-12 py-3.5 font-merriweather text-[16px] text-brand-10
                          shadow-md transition-all duration-300 hover:bg-secondary-200 hover:-translate-y-0.5 hover:shadow-lg">
                    Join Us Now!
                </a>
                {{-- deretan buku (book spines) — mengambang pelan --}}
                <div class="mt-14 flex items-end justify-center gap-1.5 px-4">
                    @php
                        $books = [
                            [150,'#9A7D66',26],[188,'#e1d7ce',22],[168,'#81644D',30],[196,'#c9beb5',24],
                            [172,'#b1a086',26],[158,'#9A7D66',22],[180,'#81644D',30],[176,'#c9beb5',24],
                            [174,'#b1a086',28],[160,'#9A7D66',26],[135,'#81644D',40],[142,'#c9beb5',34],
                            [160,'#b1a086',30],[196,'#81644D',30],
                        ];
                    @endphp
                    @foreach ($books as $i => $b)
                        <span class="animate-float-slow relative inline-block rounded-t-sm"
                              style="height: {{ $b[0] }}px; width: {{ $b[2] }}px; animation-delay: {{ $i * 0.25 }}s;
                                     background: linear-gradient(90deg, rgba(0,0,0,0.2), transparent 30%, transparent 70%, rgba(255,255,255,0.18)), {{ $b[1] }};">
                            <span class="absolute inset-x-1 top-3 h-0.5 rounded bg-black/15"></span>
                            <span class="absolute inset-x-1 bottom-2 h-1.5 rounded-sm bg-white/25"></span>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== FOOTER ===================== --}}
    <footer class="bg-[#2a1f17] py-14 text-brand-200">
        <div class="mx-auto max-w-[1240px] px-6 lg:px-[52px]">
            <div class="flex flex-col items-center gap-6 border-b border-white/10 pb-8 md:flex-row md:justify-between">
                <a href="#hero" class="flex items-center gap-2">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-secondary-150 text-brand-10 font-merriweather text-lg">S</span>
                    <span class="font-merriweather text-2xl text-brand-10">Spindle</span>
                </a>
                <ul class="flex flex-wrap items-center justify-center gap-x-9 gap-y-3 font-montserrat text-[16px]">
                    <li><a href="#hero"  class="nav-link transition-colors hover:text-brand-10">Background</a></li>
                    <li><a href="#about" class="nav-link transition-colors hover:text-brand-10">About Us</a></li>
                    <li><a href="#tools" class="nav-link transition-colors hover:text-brand-10">Guides</a></li>
                    <li><a href="#writers" class="nav-link transition-colors hover:text-brand-10">Advantages</a></li>
                </ul>
            </div>
            <p class="mt-6 text-center font-montserrat text-[14px] text-brand-200/70">
                © {{ date('Y') }} Spindle. Weave every thread into a story worth telling.
            </p>
        </div>
    </footer>

</body>
</html>
