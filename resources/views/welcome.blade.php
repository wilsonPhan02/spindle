<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Spindle — Spin A Yarn') }}</title>

    <link rel="icon" href="/favicon.png?v=5" type="image/png">
    
    <!-- Preload critical hero assets for instant paint -->
    <link rel="preload" as="image" href="{{ asset('images/landing/hero-figure.png') }}">
    <link rel="preload" as="image" href="{{ asset('images/landing/mtn-peak2.png') }}">

    <script>
        (() => {
            try {
                const stored = localStorage.getItem('theme');
                if (stored === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @keyframes mountainRise {
            0% { transform: translateY(150px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }
        .animate-mountain-rise-1 {
            animation: mountainRise 1.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
        }
        .animate-mountain-rise-2 {
            animation: mountainRise 1.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            animation-delay: 0.15s;
            opacity: 0;
        }
        .animate-mountain-rise-3 {
            animation: mountainRise 1.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            animation-delay: 0.3s;
            opacity: 0;
        }
        @keyframes book-wave {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); } /* Very subtle wave */
        }
        .animate-book-wave {
            animation: book-wave 4s ease-in-out infinite;
        }
    </style>
</head>


@php $img = fn ($f) => asset('images/landing/' . $f); @endphp

<body class="bg-brand-50 dark:bg-bg-main text-text-80 dark:text-text-100 font-montserrat antialiased overflow-x-hidden">

    
    <nav id="main-nav" class="fixed top-5 inset-x-0 z-50 transition-transform duration-700">
        <div class="mx-auto max-w-[1280px] px-6 lg:px-10">
            <div class="flex w-full items-center justify-between rounded-full bg-white dark:bg-card-bg dark:border dark:border-brand-150 py-2 pl-10 pr-2.5 shadow-lg transition-colors duration-300">
                <a href="#hero" class="flex items-center">
                    <x-logo class="h-[26px] w-auto select-none text-text-80 dark:text-text-100 transition-colors" />
                </a>
                <ul class="hidden md:flex items-center gap-6 lg:gap-10 text-web-body-small text-text-70 dark:text-text-90">
                    <li><a href="#hero"    class="nav-link hover:text-secondary-200 transition-colors">{{ __('Introduction') }}</a></li>
                    <li><a href="#about"   class="nav-link hover:text-secondary-200 transition-colors">{{ __('About Us') }}</a></li>
                    <li><a href="#writers" class="nav-link hover:text-secondary-200 transition-colors">{{ __('Challenges') }}</a></li>
                    <li><a href="#tools"   class="nav-link hover:text-secondary-200 transition-colors">{{ __('Features') }}</a></li>
                </ul>
                <div class="flex items-center gap-3 sm:gap-4">
                    <!-- Bulletproof Spindle Theme Toggle -->
                    <div x-data="{
                             isDark: document.documentElement.classList.contains('dark'),
                             toggle() {
                                 this.isDark = !this.isDark;
                                 if (this.isDark) {
                                     document.documentElement.classList.add('dark');
                                     localStorage.setItem('theme', 'dark');
                                 } else {
                                     document.documentElement.classList.remove('dark');
                                     localStorage.setItem('theme', 'light');
                                 }
                                 if (window.Alpine && Alpine.store('theme')) {
                                     Alpine.store('theme').isDark = this.isDark;
                                 }
                                 window.dispatchEvent(new CustomEvent('theme-changed', { detail: { isDark: this.isDark } }));
                             }
                         }"
                         x-init="
                             isDark = document.documentElement.classList.contains('dark');
                             window.addEventListener('theme-changed', e => isDark = e.detail.isDark);
                         "
                         class="flex items-center">
                        <button @click="toggle()" 
                                type="button"
                                class="group relative flex h-9 w-9 items-center justify-center rounded-full border border-secondary-100/50 dark:border-secondary-200/50 bg-secondary-5 dark:bg-brand-50 text-secondary-200 shadow-sm transition-all duration-300 hover:scale-105 hover:border-secondary-200 hover:shadow focus:outline-none"
                                :title="isDark ? '{{ __('Switch to Light Mode') }}' : '{{ __('Switch to Dark Mode') }}'"
                                aria-label="{{ __('Toggle Theme') }}">
                            <!-- Sun icon (shows in Dark mode) -->
                            <svg x-show="isDark" x-cloak class="h-4 w-4 text-secondary-200 transition-transform duration-300 group-hover:rotate-45" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <!-- Moon icon (shows in Light mode) -->
                            <svg x-show="!isDark" class="h-4 w-4 text-secondary-200 transition-transform duration-300 group-hover:-rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                        </button>
                    </div>

                    <div x-data="{ open: false }" 
                         x-init="$watch('open', value => { if (value) { $nextTick(() => { const el = $refs.dropdown.querySelector('.bg-gray-50'); if (el) el.scrollIntoView({ block: 'nearest' }); }) } })" 
                         class="relative hidden sm:block">
                        <button @click="open = !open" @click.away="open = false" class="flex items-center gap-2 text-web-body-small text-text-70 dark:text-text-90 hover:text-secondary-200 transition-colors focus:outline-none">
                            @php
                                $curLang = app()->getLocale();
                                $langs = [
                                    'en' => ['English', 'us'], 'id' => ['Bahasa Indonesia', 'id'],
                                    'ja' => ['日本語', 'jp'], 'zh' => ['中文 (简体)', 'cn'], 'ko' => ['한국어', 'kr'],
                                ];
                                $cc = $langs[$curLang][1] ?? 'us';
                            @endphp
                            <div class="w-6 h-6 rounded-full border border-gray-400 shadow-sm overflow-hidden shrink-0">
                                <img src="https://flagcdn.com/w40/{{ $cc }}.png" alt="{{ $curLang }}" class="w-full h-full object-cover">
                            </div>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-cloak @wheel.stop x-ref="dropdown"
                             x-transition:enter="transition ease-out duration-200 origin-top"
                             x-transition:enter-start="opacity-0 scale-y-75"
                             x-transition:enter-end="opacity-100 scale-y-100"
                             x-transition:leave="transition ease-in duration-150 origin-top"
                             x-transition:leave-start="opacity-100 scale-y-100"
                             x-transition:leave-end="opacity-0 scale-y-75"
                             class="absolute right-0 mt-2 w-48 bg-white dark:bg-card-bg border border-gray-200 dark:border-brand-150 rounded-lg shadow-lg z-50 py-1 max-h-64 overflow-y-auto overscroll-contain custom-scrollbar">
                            @foreach($langs as $code => $data)
                                <a href="{{ route('lang.switch', $code) }}" 
                                   class="flex items-center gap-3 w-full text-left px-4 py-2 text-[13px] hover:bg-gray-50 dark:hover:bg-brand-50 transition-colors {{ $curLang === $code ? 'bg-gray-50 dark:bg-brand-50 font-medium text-text-80 dark:text-text-100' : 'text-gray-600 dark:text-text-80' }}">
                                    <div class="w-5 h-5 rounded-full border border-gray-400 shadow-sm overflow-hidden shrink-0">
                                        <img src="https://flagcdn.com/w20/{{ $data[1] }}.png" alt="{{ $code }}" class="w-full h-full object-cover">
                                    </div>
                                    <span class="flex-1 min-w-0 truncate">{{ $data[0] }}</span>
                                    @if($curLang === $code)
                                        <svg class="w-4 h-4 text-secondary-200 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center rounded-full bg-secondary-150 px-9 py-3.5 text-web-body-small font-merriweather text-brand-10
                              transition-all duration-300 hover:bg-secondary-200 hover:-translate-y-0.5 hover:shadow-md">
                        {{ __('Enter the Realm') }}
                    </a>
                </div>
            </div>
        </div>
    </nav>

    
    <header id="hero" class="relative min-h-screen overflow-hidden">
        
        <div class="absolute inset-0 z-0 bg-gradient-to-b from-[#cdb99e] via-[#e4d7c3] to-[#efe6d7]"></div>

        
        <div class="pointer-events-none absolute inset-0 z-0 overflow-hidden">
            <img src="{{ $img('cloud_left.png') }}" class="absolute top-[5%] left-[-10%] w-[45%] opacity-90 animate-cloud" alt="">
            <img src="{{ $img('cloud_right.png') }}" class="absolute top-[8%] right-[-5%] w-[50%] opacity-85 animate-cloud-2" alt="">
        </div>

        
        
        <div class="pointer-events-none absolute inset-0 z-[1] parallax-wrap" data-speed="0.3">
            <img src="{{ $img('mtn-peak2.png') }}" alt="" class="absolute max-w-none select-none animate-mountain-rise-1"
                 style="bottom: -10%; left: 5%; width: 95%;">
        </div>
        
        <div class="pointer-events-none absolute inset-0 z-[2] parallax-wrap" data-speed="0.2">
            <img src="{{ $img('mtn-range.png') }}" alt="" class="absolute max-w-none select-none animate-mountain-rise-2"
                 style="bottom: -5%; left: -2%; width: 106%;">
        </div>

        
        <div class="pointer-events-none absolute z-[3] flex items-center justify-center overflow-hidden" style="bottom: 2%; right: -2%; width: 44%;">
            
            <!-- Breeze Wind Effect -->
            <div class="absolute inset-0 z-20 flex flex-col justify-center mix-blend-screen opacity-100 pointer-events-none">
                <div class="absolute top-[20%] left-0 h-[3px] w-[70%] bg-gradient-to-r from-transparent via-brand-10/80 to-transparent animate-wind-blow" style="animation-duration: 3.5s; animation-delay: 0s; filter: blur(1px);"></div>
                <div class="absolute top-[35%] left-0 h-[6px] w-[90%] bg-gradient-to-r from-transparent via-secondary-200/60 to-transparent animate-wind-blow" style="animation-duration: 4.5s; animation-delay: 1.2s; filter: blur(2px);"></div>
                <div class="absolute top-[45%] left-0 h-[2px] w-[50%] bg-gradient-to-r from-transparent via-white/70 to-transparent animate-wind-blow" style="animation-duration: 4s; animation-delay: 2.5s; filter: blur(1px);"></div>
                <div class="absolute top-[55%] left-0 h-[8px] w-[120%] bg-gradient-to-r from-transparent via-brand-10/50 to-transparent animate-wind-blow" style="animation-duration: 6s; animation-delay: 0.8s; filter: blur(3px);"></div>
                <div class="absolute top-[70%] left-0 h-[4px] w-[80%] bg-gradient-to-r from-transparent via-secondary-100/70 to-transparent animate-wind-blow" style="animation-duration: 5s; animation-delay: 2s; filter: blur(1.5px);"></div>
                <div class="absolute top-[85%] left-0 h-[3px] w-[60%] bg-gradient-to-r from-transparent via-brand-10/80 to-transparent animate-wind-blow" style="animation-duration: 4.2s; animation-delay: 3.2s; filter: blur(1px);"></div>
            </div>

            <img src="{{ $img('hero-figure.png') }}" alt="{{ __('The Weaver') }}"
                 class="animate-sway relative z-10 w-full max-w-none select-none drop-shadow-[0_14px_22px_rgba(43,31,23,0.35)]">
        </div>

        
        <div class="pointer-events-none absolute inset-x-0 bottom-[-2px] z-[5] h-[40%] bg-gradient-to-b from-transparent via-[#2a1f17]/50 to-[#2a1f17]"></div>

        
        <div class="pointer-events-none absolute inset-0 z-[4] parallax-wrap" data-speed="0.1">
            <img src="{{ $img('mountains-dark.png') }}" alt=""
                 class="absolute inset-x-[-22%] bottom-[-1px] w-[144%] max-w-none select-none animate-mountain-rise-3">
        </div>

        
        <div class="relative z-20 mx-auto max-w-[1240px] px-6 lg:px-[52px] pt-[150px]">
            <div class="max-w-[700px]">
                <h1 class="reveal text-web-title text-[#37322e]">
                    {{ __('Are You Ready To') }}<br>
                    <span class="font-merriweather italic text-secondary-200">{{ __('Spin A Yarn?') }}</span>
                </h1>
                <p class="reveal reveal-d1 mt-6 max-w-[480px] font-montserrat text-[18px] leading-[28px] text-[#524d49]">
                    {!! __('Weave every character, plot, and world into a story worth telling, spinning countless narrative threads into one cohesive universe.') !!}
                </p>
                <a href="{{ route('dashboard') }}"
                   class="mt-8 inline-flex items-center rounded-[7px] bg-secondary-150 px-10 py-3.5 font-merriweather text-[16px] text-brand-10
                          shadow-md transition-all duration-700 hover:bg-secondary-200 hover:-translate-y-1 hover:shadow-lg">
                    {{ __('Enter the Realm') }}
                </a>
            </div>
        </div>
    </header>

    
    <div id="dark-universe" class="relative">
        
        <section class="relative bg-[#2a1f17]">
            
            <div class="pointer-events-none absolute inset-0 z-0">
                <div class="absolute right-[0%] top-[55%] h-[750px] w-[1100px] max-w-none -translate-y-1/2">
                    <canvas id="galaxy-particle-canvas" class="h-full w-full"></canvas>
                </div>
            </div>
            @include('partials.starfield')
            <div class="relative z-10 mx-auto flex min-h-screen max-w-[1240px] flex-col justify-center px-6 lg:px-[52px] py-40">
                <h2 class="reveal text-web-title leading-tight text-[#F3ECE3] max-w-[600px]">{!! __('The Creative Realms') !!}</h2>
                <p class="reveal reveal-d1 mt-6 max-w-[520px] font-montserrat text-[18px] leading-[28px] text-[#E3DBD0]">
                    {{ __('In a Universe known as') }} <span class="font-merriweather italic text-[#CAB79B]">{{ __('“The Creative Realms”') }}</span><br>
                    {{ __('Lived the Writer of Worlds. They possess the power to create life from the void.') }}
                </p>
            </div>
        </section>

        
        <section class="relative min-h-screen flex flex-col justify-center overflow-hidden bg-[#2a1f17] py-20">
            @include('partials.starfield')
            <div class="relative z-10 mx-auto w-full max-w-[1240px] px-6 lg:px-[52px] text-center mt-6 lg:mt-8">
                <p class="reveal font-montserrat text-[18px] text-[#E3DBD0]">{{ __('However, every Writer faces the same monster') }}</p>
                <h2 class="reveal reveal-d1 mt-2 font-merriweather text-[44px] font-bold italic text-[#F3ECE3] mx-auto max-w-[700px]">{{ __('“The Great Tangle”') }}</h2>

                <div class="mt-14 flex flex-wrap items-center justify-center gap-6" style="perspective: 1500px;">
                    @php
                        $tangles = [
                            ['file' => 'tangle-1.png', 'tilt' => 'rotate-[-5deg]', 'text' => 'Timelines twist into<br>deadly plot-holes...'],
                            ['file' => 'tangle-2.png', 'tilt' => 'lg:-translate-y-4 z-10', 'text' => 'When a story grows vast,<br>magic begins to falter...'],
                            ['file' => 'tangle-3.png', 'tilt' => 'rotate-[5deg]', 'text' => 'The army of characters<br>begins to lose its way...']
                        ];
                    @endphp
                    @foreach($tangles as $tangle)
                        <div class="reveal reveal-d{{ $loop->iteration }} {{ $tangle['tilt'] }} w-[300px] max-w-[80%] transition-all duration-500 hover:z-20">
                            <div class="animate-float hover:[animation-play-state:paused]" style="animation-delay: {{ $loop->index * 1.1 }}s; transform-style: preserve-3d;">
                                <div class="tilt-container relative w-full h-auto cursor-pointer" style="perspective: 1000px;">
                                    <div class="tilt-element relative w-full h-auto drop-shadow-[0_24px_40px_rgba(0,0,0,0.45)] transition-transform duration-[200ms] ease-out pointer-events-none"
                                         style="transform-style: preserve-3d; transform: rotateX(0deg) rotateY(0deg) scale(1);">
                                         <img src="{{ $img($tangle['file']) }}" alt="{{ __('The Great Tangle') }}" loading="lazy" class="w-full h-auto block">
                                         
                                         <!-- Localized Text Overlay -->
                                         <div class="absolute bottom-[10.5%] inset-x-4 flex justify-center text-center">
                                             <p class="font-merriweather text-[14px] leading-[22px] text-[#554c46] dark:text-[#2E2A25]">
                                                 {!! __($tangle['text']) !!}
                                             </p>
                                         </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    
    <section id="about" class="relative min-h-screen flex flex-col justify-center overflow-hidden bg-[#2a1f17] py-20 scroll-mt-24">
        @include('partials.starfield')
        <div class="relative z-10 mx-auto grid max-w-[1240px] grid-cols-1 items-center gap-12 px-6 lg:px-[52px] lg:grid-cols-2 mt-12 lg:mt-24">
            
            <div class="reveal reveal-left relative order-2 h-[500px] w-full lg:order-1">
                
                <!-- Hand Image -->
                <img src="{{ $img('hand.png') }}" alt="" loading="lazy"
                     class="pointer-events-none absolute top-1/2 left-[-15%] lg:left-[-8%] xl:left-[-12%] 2xl:left-[-18%] z-0 w-[100%] lg:w-[500px] max-w-none -translate-y-[40%] select-none transition-all duration-300">
                
                <!-- Spindle Tool/Card (Foreground with Float Animation) -->
                <div class="absolute top-1/2 left-[24%] lg:left-[30%] xl:left-[26%] 2xl:left-[20%] z-10 w-[200px] lg:w-[260px] -translate-y-[100%] animate-float transition-all duration-300">
                    <img src="{{ $img('group47.png') }}" alt="{{ __('The Spindle') }}" loading="lazy"
                         class="w-full rotate-[8deg] drop-shadow-[0_30px_40px_rgba(0,0,0,0.5)]">
                </div>
            </div>

            <div class="reveal reveal-right order-1 lg:order-2 -mt-12 lg:-mt-24">
                <p class="font-montserrat text-[18px] font-semibold text-[#CAB79B]">{{ __('WHO WE ARE?') }}</p>
                <h2 class="mt-1 text-web-title text-[#F3ECE3] max-w-[600px]">{!! __('But Spindle Come as Solution') !!}</h2>
                <p class="mt-6 max-w-[500px] font-montserrat text-[18px] leading-[28px] text-[#E3DBD0] opacity-90">
                    {{ __('We are going to help you create your story. Weave stories, characters, and notes easily by using Spindle. Convert your abstract yarn into a magic yarn!') }}
                </p>
            </div>
        </div>
    </section>
</div>

    
    <section id="writers" class="relative min-h-screen flex flex-col justify-center overflow-hidden bg-brand-50 dark:bg-bg-main py-20 scroll-mt-24">
        <div class="mx-auto max-w-[1240px] px-6 lg:px-[52px] text-center -mt-4">
            <p class="reveal font-montserrat text-[18px] font-semibold text-secondary-200">{{ __('OUR MISSION') }}</p>
            <h2 class="mt-1 text-web-title text-text-80 dark:text-text-100 mx-auto max-w-[700px]">
                {{ __('From Writers To') }} <span class="font-merriweather italic text-secondary-200">{{ __('Writers') }}</span>
            </h2>
            <p class="mx-auto mt-4 max-w-[560px] font-montserrat text-[18px] leading-[28px] text-text-70 dark:text-text-90">
                {{ __('We are going to help you create your story. Weave stories, characters, and notes easily by using Spindle. Convert your abstract yarn into a magic yarn!') }}
            </p>

            <!-- Infinite Marquee Carousel -->
            <div class="relative mt-6 overflow-x-clip overflow-y-visible w-full max-w-full pb-16 pt-4">
                <!-- Shadow overlays for smooth fading edges -->
                <div class="pointer-events-none absolute inset-y-0 left-0 z-10 w-12 md:w-32 bg-gradient-to-r from-brand-50 dark:from-bg-main to-transparent"></div>
                <div class="pointer-events-none absolute inset-y-0 right-0 z-10 w-12 md:w-32 bg-gradient-to-l from-brand-50 dark:from-bg-main to-transparent"></div>
                
                <div class="flex w-max animate-[marquee_50s_linear_infinite]" id="scale-carousel-track">
                    <!-- Group 1 -->
                    <div class="flex w-max">
                        @for ($i = 0; $i < 4; $i++)
                            <div class="carousel-scale-item w-[70vw] sm:w-[400px] lg:w-[500px] shrink-0 px-2 transition-transform duration-75">
                                <div class="overflow-hidden rounded-2xl border border-card-border bg-card-bg shadow-[0_20px_40px_rgba(43,31,23,0.25)]">
                                    <img src="{{ $img('writers-center.png') }}" alt="{{ __('Spindle dashboard preview') }}" loading="lazy" class="w-full object-cover">
                                </div>
                            </div>
                        @endfor
                    </div>
                    <!-- Group 2 (Exact duplicate for seamless loop) -->
                    <div class="flex w-max">
                        @for ($i = 0; $i < 4; $i++)
                            <div class="carousel-scale-item w-[70vw] sm:w-[400px] lg:w-[500px] shrink-0 px-2 transition-transform duration-75">
                                <div class="overflow-hidden rounded-2xl border border-card-border bg-card-bg shadow-[0_20px_40px_rgba(43,31,23,0.25)]">
                                    <img src="{{ $img('writers-center.png') }}" alt="{{ __('Spindle dashboard preview') }}" loading="lazy" class="w-full object-cover">
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </section>

    
    <section id="tools" class="relative min-h-screen flex flex-col justify-center overflow-hidden bg-brand-50 dark:bg-bg-main pt-24 pb-8 scroll-mt-32">
        <div class="mx-auto max-w-[1240px] px-6 lg:px-[52px] -mt-16">
            <p class="reveal font-montserrat text-[18px] font-semibold text-secondary-200">{{ __('WHY CHOOSE US') }}</p>
            <h2 class="reveal reveal-d1 mt-1 max-w-[599px] text-web-title text-text-80 dark:text-text-100">{{ __('We Provide A Tools For Writing') }}</h2>

            @php $base = asset('images/landing') . '/'; @endphp
            <div x-data="{ 
                    sel: 0, 
                    base: '{{ $base }}', 
                    timer: null,
                    items: [
                        { t: '{{ __('Create a New Project') }}', d: '{{ __('Sections act as unified directories to organize and store every series of your creative works.') }}', img: 'writers-center.png' },
                        { t: '{{ __('Choose a Structure') }}', d: '{{ __('Select the perfect narrative framework to seamlessly organize your timeline, plots, and acts.') }}', img: 'writers-center.png' },
                        { t: '{{ __('Create Character Relationship') }}', d: '{{ __('Map out complex character relationships to ensure consistency across your storytelling universe.') }}', img: 'writers-center.png' },
                        { t: '{{ __('Add Your Notes') }}', d: '{{ __('Consolidate your lore, world-building notes, and untamed ideas into a single, accessible repository.') }}', img: 'writers-center.png' },
                    ],
                    startTimer() {
                        this.timer = setInterval(() => { this.sel = (this.sel + 1) % this.items.length; }, 3500);
                    },
                    stopTimer() {
                        clearInterval(this.timer);
                    }
                 }"
                 x-init="startTimer()"
                 @mouseenter="stopTimer()"
                 @mouseleave="startTimer()"
                 class="mt-8 grid grid-cols-1 items-center gap-10 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)] lg:gap-14">
                
                <div class="reveal reveal-left order-2 lg:order-1 w-full lg:w-11/12 lg:ml-auto">
                      <div class="relative aspect-[566/419] w-full" style="perspective: 1500px; transform-style: preserve-3d;">
                          <template x-for="(it, i) in items" :key="i">
                              <div class="absolute inset-0 overflow-hidden rounded-sm border border-secondary-200/30 bg-brand-100 shadow-[0_15px_30px_rgba(43,31,23,0.15)] transition-all duration-[1200ms] ease-[cubic-bezier(0.4,0,0.2,1)]"
                                   :style="sel === i ? 'transform: translate3d(0px, 0px, 50px) rotate(0deg) scale(1); opacity: 1;' : 
                                           `transform: translate3d(${ (i - sel) * 16 }px, ${ Math.abs(i - sel) * 12 }px, ${ -Math.abs(i - sel) * 60 }px) rotate(${ (i - sel) * 4 }deg) scale(${ 1 - Math.abs(i - sel)*0.02 }); opacity: ${ 1 - Math.abs(i - sel)*0.25 };`">
                                  <img :src="base + it.img" class="h-full w-full object-cover object-top pointer-events-none" alt="">
                              </div>
                          </template>
                      </div>
                  </div>

                  <div class="reveal reveal-right order-1 space-y-3 lg:order-2 w-full lg:max-w-[480px]">
                      <template x-for="(it, i) in items" :key="i">
                          <button type="button" x-on:click="sel = i" x-on:mouseenter="sel = i"
                                  class="block w-full rounded-[6px] border-2 px-7 text-left transition-all duration-[800ms] ease-[cubic-bezier(0.4,0,0.2,1)]"
                                  :class="sel === i ? 'border-secondary-200 bg-brand-150 dark:bg-card-bg pt-5 pb-5' : 'border-brand-200 dark:border-brand-150 py-4 hover:border-secondary-150 hover:bg-brand-10 dark:hover:bg-brand-50'">
                              <span class="font-merriweather text-[20px] italic transition-colors duration-[800ms]"
                                    :class="sel === i ? 'text-secondary-200 dark:text-secondary-50' : 'text-text-70 dark:text-text-80'" x-text="it.t"></span>
                              <div class="grid transition-all duration-[800ms] ease-[cubic-bezier(0.4,0,0.2,1)]"
                                   :class="sel === i ? 'grid-rows-[1fr] opacity-100 mt-2' : 'grid-rows-[0fr] opacity-0 mt-0'">
                                  <div class="overflow-hidden">
                                      <p class="font-montserrat text-[14px] leading-[24px] text-text-80 dark:text-text-90 h-[72px]" x-text="it.d"></p>
                                  </div>
                              </div>
                          </button>
                      </template>
                  </div>
            </div>
        </div>
    </section>

    <section class="relative min-h-screen flex flex-col justify-center overflow-hidden bg-brand-50 dark:bg-bg-main py-20">
        <!-- Wind Rings Background -->
        <style>
            .custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #8C7558; }
            @keyframes wind-ring {
                0% { transform: scale(0.3) rotate(0deg); opacity: 0; border-width: 8px; }
                20% { opacity: 0.35; }
                100% { transform: scale(3.5) rotate(180deg); opacity: 0; border-width: 2px; }
            }
            .animate-wind-ring {
                animation: wind-ring 6s cubic-bezier(0.21, 0.53, 0.3, 1) infinite;
            }
        </style>
        <div class="absolute inset-0 z-0 flex items-center justify-center pointer-events-none">
            <div class="absolute w-[250px] h-[250px] md:w-[350px] md:h-[350px] border border-secondary-200 animate-wind-ring" style="border-radius: 40% 60% 60% 40% / 50% 40% 60% 50%;"></div>
            <div class="absolute w-[250px] h-[250px] md:w-[350px] md:h-[350px] border border-brand-200 animate-wind-ring" style="border-radius: 60% 40% 30% 70% / 60% 50% 50% 40%; animation-delay: -2s;"></div>
            <div class="absolute w-[250px] h-[250px] md:w-[350px] md:h-[350px] border border-secondary-100 animate-wind-ring" style="border-radius: 50% 50% 40% 60% / 40% 60% 40% 60%; animation-delay: -4s;"></div>
        </div>

        <div class="relative z-10 mx-auto max-w-[1240px] px-6 lg:px-[52px] text-center">
            <p class="reveal mx-auto max-w-[800px] font-merriweather text-[28px] md:text-[36px] lg:text-[42px] italic leading-[1.5] text-text-80 dark:text-text-100">
                {{ __('You Are The') }} <span class="text-secondary-200">{{ __('“Weavers”') }}</span>...<br class="hidden md:block">
                {{ __('The Ones Who Will Turn A Yarn Into A Story.') }}
            </p>
        </div>
    </section>

    <section class="relative overflow-hidden bg-brand-50 dark:bg-bg-main pt-16 pb-0">
        <div class="reveal reveal-d1 relative mx-auto max-w-[1240px] px-6 lg:px-[52px]">
            <div class="relative overflow-hidden rounded-[28px] px-6 pt-20 mb-16 text-center shadow-lg" style="background-color: #E9E1DA;">
                <h2 class="text-web-title text-[#231D18] mx-auto max-w-[700px]">
                    {{ __('Are You Ready To') }} <span class="font-merriweather italic text-[#81644D]">{{ __('Spin A Yarn?') }}</span>
                </h2>
                <p class="mx-auto mt-4 max-w-[580px] font-montserrat text-[18px] leading-[28px] text-[#37322E]">
                    {{ __('Weave every character, plot, and world into a story worth telling, spinning countless narrative threads into one cohesive universe.') }}
                </p>
                <a href="{{ route('dashboard') }}"
                   class="mt-6 inline-flex items-center rounded-[7px] bg-secondary-150 px-10 py-3.5 font-merriweather text-[16px]
                          shadow-md transition-all duration-700 hover:bg-secondary-200 hover:-translate-y-1 hover:shadow-lg"
                   style="color: #F7F5F4;">
                    {{ __('Enter the Realm') }}
                </a>
                
                <div class="mt-10 flex justify-center px-4 pb-10">
                    <!-- Tight wrapper so hover only triggers when actually on the books -->
                    <div id="books-container" class="flex items-end gap-2 cursor-pointer h-[280px]">
                        @php
                            $books = [
                                [210,'#9A7D66',34],[264,'#e1d7ce',30],[236,'#81644D',40],[276,'#c9beb5',32],
                                [242,'#b1a086',34],[222,'#9A7D66',30],[252,'#81644D',40],[248,'#c9beb5',32],
                                [244,'#b1a086',36],[224,'#9A7D66',34],[190,'#81644D',54],[200,'#c9beb5',46],
                                [224,'#b1a086',40],[276,'#81644D',40],
                            ];
                        @endphp
                        @foreach($books as $i => $b)
                            <span class="book-wrapper animate-book-wave inline-block" style="animation-delay: {{ $i * 0.2 }}s;">
                                <span class="book-item relative inline-block rounded-t-sm origin-bottom"
                                      style="height: {{ $b[0] }}px; width: {{ $b[2] }}px;
                                             background: linear-gradient(90deg, rgba(0,0,0,0.2), transparent 30%, transparent 70%, rgba(255,255,255,0.18)), {{ $b[1] }};
                                             box-shadow: 0 5px 15px rgba(0,0,0,0.25);">
                                    <span class="absolute inset-x-1 top-4 h-0.5 rounded bg-black/15"></span>
                                    <span class="absolute inset-x-1 bottom-3 h-2 rounded-sm bg-white/25"></span>
                                </span>
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    
    <footer class="bg-black py-16 text-white">
        <div class="mx-auto flex max-w-[1240px] flex-col items-center px-6 text-center">
            <a href="#hero" class="flex items-center">
                <x-logo class="h-9 w-auto select-none text-white" />
            </a>
            <ul class="mt-7 flex flex-wrap items-center justify-center gap-x-10 gap-y-3 font-montserrat text-[15px] font-semibold tracking-wide">
                <li><a href="#hero"    class="transition-colors hover:text-white/70">{{ __('INTRODUCTION') }}</a></li>
                <li><a href="#about"   class="transition-colors hover:text-white/70">{{ __('ABOUT US') }}</a></li>
                <li><a href="#writers" class="transition-colors hover:text-white/70">{{ __('CHALLENGES') }}</a></li>
                <li><a href="#tools"   class="transition-colors hover:text-white/70">{{ __('FEATURES') }}</a></li>
            </ul>
            <p class="mt-7 max-w-[640px] font-montserrat text-[15px] leading-[26px] text-white/70">
                {!! __('Copyright © :year Spindle. Empowering storytellers to weave unforgettable narratives. Developed and maintained by the Spindle Team. All rights reserved.', ['year' => date('Y')]) !!}
            </p>
            <div class="mt-8 flex items-center gap-4">
                <a href="#" aria-label="{{ __('Instagram') }}" class="flex h-11 w-11 items-center justify-center rounded-full border border-white/40 transition-colors hover:bg-white/10">
                    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.8">
                        <rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>
                    </svg>
                </a>
                <a href="#" aria-label="{{ __('Email') }}" class="flex h-11 w-11 items-center justify-center rounded-full border border-white/40 transition-colors hover:bg-white/10">
                    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.8">
                        <rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>
                    </svg>
                </a>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <a href="#hero" id="back-to-top" aria-label="{{ __('Back to top') }}"
       class="fixed bottom-8 right-8 z-50 flex h-12 w-12 translate-y-[150%] items-center justify-center rounded-full bg-secondary-150 text-brand-10 opacity-0 shadow-[0_8px_16px_rgba(43,31,23,0.2)] transition-all duration-500 hover:bg-secondary-200 hover:-translate-y-1 hover:shadow-[0_12px_24px_rgba(43,31,23,0.3)]">
        <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 15l7-7 7 7"/>
        </svg>
    </a>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>
