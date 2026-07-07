<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Spindle — Spin A Yarn</title>

    <link rel="icon" href="/favicon.png?v=5" type="image/png">
    
    <!-- Preload critical hero assets for instant paint -->
    <link rel="preload" as="image" href="{{ asset('images/landing/hero-figure.png') }}">
    <link rel="preload" as="image" href="{{ asset('images/landing/mtn-peak2.png') }}">

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

<body class="bg-brand-50 text-text-80 font-montserrat antialiased overflow-x-hidden">

    
    <nav id="main-nav" class="fixed top-5 inset-x-0 z-50 transition-transform duration-700">
        <div class="mx-auto max-w-[1280px] px-6 lg:px-10">
            <div class="flex w-full items-center justify-between rounded-full bg-white py-2 pl-10 pr-2.5 shadow-lg">
                <a href="#hero" class="flex items-center">
                    <img src="{{ $img('logo-spindle.png') }}" alt="Spindle" class="h-[26px] w-auto select-none">
                </a>
                <ul class="hidden md:flex items-center gap-12 text-web-body-small text-text-70">
                    <li><a href="#hero"    class="nav-link hover:text-secondary-200 transition-colors">Introduction</a></li>
                    <li><a href="#about"   class="nav-link hover:text-secondary-200 transition-colors">About Us</a></li>
                    <li><a href="#tools"   class="nav-link hover:text-secondary-200 transition-colors">Guides</a></li>
                    <li><a href="#writers" class="nav-link hover:text-secondary-200 transition-colors">Advantages</a></li>
                </ul>
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center rounded-full bg-secondary-150 px-9 py-3.5 text-web-body-small font-merriweather text-brand-10
                          transition-all duration-300 hover:bg-secondary-200 hover:-translate-y-0.5 hover:shadow-md">
                    Join Us Now
                </a>
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

            <img src="{{ $img('hero-figure.png') }}" alt="The Weaver"
                 class="animate-sway relative z-10 w-full max-w-none select-none drop-shadow-[0_14px_22px_rgba(43,31,23,0.35)]">
        </div>

        
        <div class="pointer-events-none absolute inset-x-0 bottom-[-2px] z-[5] h-[40%] bg-gradient-to-b from-transparent via-[#2a1f17]/50 to-[#2a1f17]"></div>

        
        <div class="pointer-events-none absolute inset-0 z-[4] parallax-wrap" data-speed="0.1">
            <img src="{{ $img('mountains-dark.png') }}" alt=""
                 class="absolute inset-x-[-22%] bottom-[-1px] w-[144%] max-w-none select-none animate-mountain-rise-3">
        </div>

        
        <div class="relative z-20 mx-auto max-w-[1240px] px-6 lg:px-[52px] pt-[150px]">
            <div class="max-w-[560px]">
                <h1 class="reveal text-web-title text-text-80">
                    Are You Ready To<br>
                    <span class="font-merriweather italic text-secondary-200">Spin A Yarn?</span>
                </h1>
                <p class="reveal reveal-d1 mt-6 max-w-[480px] font-montserrat text-[18px] leading-[28px] text-text-70">
                    Weave every character, plot, and world into a story
                    <br>worth telling, spinning countless narrative
                    <br> threads into one cohesive universe.
                </p>
                <a href="{{ route('dashboard') }}"
                   class="mt-8 inline-flex items-center rounded-[7px] bg-secondary-150 px-10 py-3.5 font-merriweather text-[16px] text-brand-10
                          shadow-md transition-all duration-700 hover:bg-secondary-200 hover:-translate-y-1 hover:shadow-lg">
                    Get Started
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
                <h2 class="reveal text-web-title leading-tight text-brand-10">The Creative<br>Realism</h2>
                <p class="reveal reveal-d1 mt-6 max-w-[520px] font-montserrat text-[18px] leading-[28px] text-brand-200">
                    In a Universe known as <span class="font-merriweather italic text-secondary-50">“The Creative Realms”</span>
                    Lived the Writer of Worlds. They possess the power to create life from the void.
                </p>
            </div>
        </section>

        
        <section class="relative min-h-screen flex flex-col justify-center overflow-hidden bg-[#2a1f17] py-20">
            @include('partials.starfield')
            <div class="relative z-10 mx-auto w-full max-w-[1240px] px-6 lg:px-[52px] text-center mt-6 lg:mt-8">
                <p class="reveal font-montserrat text-[18px] text-brand-200">However, every Writer faces the same monster</p>
                <h2 class="reveal reveal-d1 mt-2 font-merriweather text-[44px] font-bold italic text-brand-10">“The Great Tangle”</h2>

                <div class="mt-14 flex flex-wrap items-center justify-center gap-6" style="perspective: 1500px;">
                    @foreach(['tangle-1.png' => 'rotate-[-5deg]', 'tangle-2.png' => 'lg:-translate-y-4 z-10', 'tangle-3.png' => 'rotate-[5deg]'] as $file => $tilt)
                        <div class="reveal reveal-d{{ $loop->iteration }} {{ $tilt }} w-[300px] max-w-[80%] transition-all duration-500 hover:z-20">
                            <div class="animate-float hover:[animation-play-state:paused]" style="animation-delay: {{ $loop->index * 1.1 }}s; transform-style: preserve-3d;">
                                <div class="tilt-container relative w-full h-auto cursor-pointer" style="perspective: 1000px;">
                                    <img src="{{ $img($file) }}" alt="The Great Tangle" loading="lazy"
                                         class="tilt-element w-full h-auto drop-shadow-[0_24px_40px_rgba(0,0,0,0.45)] transition-transform duration-[200ms] ease-out pointer-events-none"
                                         style="transform-style: preserve-3d; transform: rotateX(0deg) rotateY(0deg) scale(1);">
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
                
                <img src="{{ $img('hand.png') }}" alt="" loading="lazy"
                     class="pointer-events-none absolute top-1/2 left-[-15%] lg:left-[-10%] z-0 w-[100%] lg:w-[500px] max-w-none -translate-y-[40%] select-none">
                
                <!-- Spindle Tool/Card (Foreground) -->
                <img src="{{ $img('group47.png') }}" alt="The Spindle" loading="lazy"
                     class="animate-float absolute top-1/2 left-[24%] lg:left-[28%] z-10 w-[200px] lg:w-[260px] -translate-y-[85%] rotate-[8deg] drop-shadow-[0_30px_40px_rgba(0,0,0,0.5)]">
            </div>

            <div class="reveal reveal-right order-1 lg:order-2 -mt-12 lg:-mt-24">
                <p class="font-montserrat text-[18px] font-semibold text-brand-200">WHO WE ARE?</p>
                <h2 class="mt-1 text-web-title text-brand-10">But Spindle<br>Come as Solution</h2>
                <p class="mt-6 max-w-[500px] font-montserrat text-[18px] leading-[28px] text-brand-200 opacity-90">
                    We are going to help you create your story. Weave stories, characters, and notes
                    easily by using Spindle. Convert your abstract yarn into a magic yarn!
                </p>
            </div>
        </div>
    </section>
</div>

    
    <section id="writers" class="relative min-h-screen flex flex-col justify-center overflow-hidden bg-brand-50 py-20 scroll-mt-24">
        <div class="mx-auto max-w-[1240px] px-6 lg:px-[52px] text-center -mt-4">
            <p class="reveal font-montserrat text-[18px] font-semibold text-secondary-200">OUR MISSION</p>
            <h2 class="mt-1 text-web-title text-text-80">
                From Writers To <span class="font-merriweather italic text-secondary-200">Writers</span>
            </h2>
            <p class="mx-auto mt-4 max-w-[560px] font-montserrat text-[18px] leading-[28px] text-text-70">
                We are going to help you create your story. Weave stories, characters, and notes
                easily by using Spindle. Convert your abstract yarn into a magic yarn!
            </p>

            <!-- Infinite Marquee Carousel -->
            <div class="relative mt-6 overflow-x-clip overflow-y-visible w-full max-w-full pb-16 pt-4">
                <!-- Shadow overlays for smooth fading edges -->
                <div class="pointer-events-none absolute inset-y-0 left-0 z-10 w-12 md:w-32 bg-gradient-to-r from-brand-50 to-transparent"></div>
                <div class="pointer-events-none absolute inset-y-0 right-0 z-10 w-12 md:w-32 bg-gradient-to-l from-brand-50 to-transparent"></div>
                
                <div class="flex w-max animate-[marquee_50s_linear_infinite]" id="scale-carousel-track">
                    <!-- Group 1 -->
                    <div class="flex w-max">
                        @for ($i = 0; $i < 4; $i++)
                            <div class="carousel-scale-item w-[70vw] sm:w-[400px] lg:w-[500px] shrink-0 px-2 transition-transform duration-75">
                                <div class="overflow-hidden rounded-2xl border border-card-border bg-card-bg shadow-[0_20px_40px_rgba(43,31,23,0.25)]">
                                    <img src="{{ $img('writers-center.png') }}" alt="Spindle dashboard preview" loading="lazy" class="w-full object-cover">
                                </div>
                            </div>
                        @endfor
                    </div>
                    <!-- Group 2 (Exact duplicate for seamless loop) -->
                    <div class="flex w-max">
                        @for ($i = 0; $i < 4; $i++)
                            <div class="carousel-scale-item w-[70vw] sm:w-[400px] lg:w-[500px] shrink-0 px-2 transition-transform duration-75">
                                <div class="overflow-hidden rounded-2xl border border-card-border bg-card-bg shadow-[0_20px_40px_rgba(43,31,23,0.25)]">
                                    <img src="{{ $img('writers-center.png') }}" alt="Spindle dashboard preview" loading="lazy" class="w-full object-cover">
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </section>

    
    <section id="tools" class="relative min-h-screen flex flex-col justify-center overflow-hidden bg-brand-50 pt-24 pb-8 scroll-mt-32">
        <div class="mx-auto max-w-[1240px] px-6 lg:px-[52px] -mt-16">
            <p class="reveal font-montserrat text-[18px] font-semibold text-secondary-200">WHY CHOOSE US</p>
            <h2 class="reveal reveal-d1 mt-1 max-w-[599px] text-web-title text-text-80">We Provide A Tools For Writing</h2>

            @php $base = asset('images/landing') . '/'; @endphp
            <div x-data="{ 
                    sel: 0, 
                    base: '{{ $base }}', 
                    timer: null,
                    items: [
                        { t: 'Create a New Project', d: 'Sections act as unified directories to organize and store every series of your creative works.', img: 'writers-center.png' },
                        { t: 'Choose a Structure', d: 'Select the perfect narrative framework to seamlessly organize your timeline, plots, and acts.', img: 'writers-center.png' },
                        { t: 'Create Character Relationship', d: 'Map out complex character relationships to ensure consistency across your storytelling universe.', img: 'writers-center.png' },
                        { t: 'Add Your Notes', d: 'Consolidate your lore, world-building notes, and untamed ideas into a single, accessible repository.', img: 'writers-center.png' },
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
                 class="mt-8 grid grid-cols-1 items-center gap-10 lg:grid-cols-2 lg:gap-12">
                
                <div class="reveal reveal-left order-2 lg:order-1 w-11/12 mx-auto lg:w-11/12 lg:ml-auto">
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
                                  :class="sel === i ? 'border-secondary-200 bg-brand-150 pt-5 pb-5' : 'border-brand-200 py-4 hover:border-secondary-150 hover:bg-brand-10'">
                              <span class="font-merriweather text-[20px] italic transition-colors duration-[800ms]"
                                    :class="sel === i ? 'text-secondary-200' : 'text-secondary-100'" x-text="it.t"></span>
                              <div class="grid transition-all duration-[800ms] ease-[cubic-bezier(0.4,0,0.2,1)]"
                                   :class="sel === i ? 'grid-rows-[1fr] opacity-100 mt-2' : 'grid-rows-[0fr] opacity-0 mt-0'">
                                  <div class="overflow-hidden">
                                      <p class="font-montserrat text-[14px] leading-[24px] text-black" x-text="it.d"></p>
                                  </div>
                              </div>
                          </button>
                      </template>
                  </div>
            </div>
        </div>
    </section>

    <section class="relative min-h-screen flex flex-col justify-center overflow-hidden bg-brand-50 py-20">
        <!-- Wind Rings Background -->
        <style>
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
            <p class="reveal mx-auto max-w-[800px] font-merriweather text-[28px] md:text-[36px] lg:text-[42px] italic leading-[1.5] text-text-80">
                You Are The <span class="text-secondary-200">“Weavers”</span>...<br class="hidden md:block">
                The Ones Who Will Turn A Yarn Into A Story.
            </p>
        </div>
    </section>

    <section class="relative overflow-hidden bg-brand-50 pt-16 pb-0">
        <div class="reveal reveal-d1 relative mx-auto max-w-[1240px] px-6 lg:px-[52px]">
            <div class="relative overflow-hidden rounded-[28px] bg-brand-100 px-6 pt-20 mb-16 text-center shadow-lg">
                <h2 class="text-web-title text-text-80">
                    Are You Ready To <span class="font-merriweather italic text-secondary-200">Spin A Yarn?</span>
                </h2>
                <a href="{{ route('dashboard') }}"
                   class="mt-6 inline-flex items-center rounded-[7px] bg-secondary-150 px-10 py-3.5 font-merriweather text-[16px] text-brand-10
                          shadow-md transition-all duration-700 hover:bg-secondary-200 hover:-translate-y-1 hover:shadow-lg">
                    Join Us Now
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
                <img src="{{ $img('logo-spindle.png') }}" alt="Spindle" class="h-9 w-auto select-none" style="filter: brightness(0) invert(1);">
            </a>
            <ul class="mt-7 flex flex-wrap items-center justify-center gap-x-10 gap-y-3 font-montserrat text-[15px] font-semibold tracking-wide">
                <li><a href="#hero"    class="transition-colors hover:text-white/70">INTRODUCTION</a></li>
                <li><a href="#about"   class="transition-colors hover:text-white/70">ABOUT US</a></li>
                <li><a href="#tools"   class="transition-colors hover:text-white/70">GUIDES</a></li>
                <li><a href="#writers" class="transition-colors hover:text-white/70">ADVANTAGES</a></li>
            </ul>
            <p class="mt-7 max-w-[640px] font-montserrat text-[15px] leading-[26px] text-white/70">
                Copyright © {{ date('Y') }} Spindle. Empowering storytellers to weave unforgettable narratives.
                Developed and maintained by the Spindle Team. All rights reserved.
            </p>
            <div class="mt-8 flex items-center gap-4">
                <a href="#" aria-label="Instagram" class="flex h-11 w-11 items-center justify-center rounded-full border border-white/40 transition-colors hover:bg-white/10">
                    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.8">
                        <rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>
                    </svg>
                </a>
                <a href="#" aria-label="Email" class="flex h-11 w-11 items-center justify-center rounded-full border border-white/40 transition-colors hover:bg-white/10">
                    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.8">
                        <rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>
                    </svg>
                </a>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <a href="#hero" id="back-to-top" aria-label="Back to top"
       class="fixed bottom-8 right-8 z-50 flex h-12 w-12 translate-y-[150%] items-center justify-center rounded-full bg-secondary-150 text-brand-10 opacity-0 shadow-[0_8px_16px_rgba(43,31,23,0.2)] transition-all duration-500 hover:bg-secondary-200 hover:-translate-y-1 hover:shadow-[0_12px_24px_rgba(43,31,23,0.3)]">
        <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 15l7-7 7 7"/>
        </svg>
    </a>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        // Reveal on Scroll Effect
        const reveals = document.querySelectorAll('.reveal');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                }
            });
        }, {
            root: null,
            rootMargin: '0px',
            threshold: 0.15
        });

        reveals.forEach(el => observer.observe(el));
        
        // Elegant Ribbon/Thread Cursor Trail
        document.addEventListener("DOMContentLoaded", () => {
            const canvas = document.createElement('canvas');
            canvas.style.position = 'fixed';
            canvas.style.top = '0';
            canvas.style.left = '0';
            canvas.style.pointerEvents = 'none';
            canvas.style.zIndex = '9999'; // High z-index to appear on all sections
            document.body.appendChild(canvas);
            const ctx = canvas.getContext('2d');
            
            let width = window.innerWidth;
            let height = window.innerHeight;
            canvas.width = width;
            canvas.height = height;
            
            window.addEventListener('resize', () => {
                width = window.innerWidth;
                height = window.innerHeight;
                canvas.width = width;
                canvas.height = height;
            });
            
            let mouse = { x: -100, y: -100 };
            const points = Array(35).fill().map(() => ({ x: -100, y: -100 }));
            
            window.addEventListener('mousemove', (e) => {
                // Only show thread cursor over the hero section
                if (e.target && e.target.closest && (!e.target.closest('#hero'))) {
                    mouse.x = -100;
                    return;
                }
                
                // Use clientX/clientY because canvas is fixed to viewport
                if (mouse.x === -100) {
                    points.forEach(p => { p.x = e.clientX; p.y = e.clientY; });
                }
                mouse.x = e.clientX;
                mouse.y = e.clientY;
            });
            
            function draw() {
                ctx.clearRect(0, 0, width, height);
                
                // The spring/follower algorithm for perfectly smooth curves
                points[0].x += (mouse.x - points[0].x) * 0.4;
                points[0].y += (mouse.y - points[0].y) * 0.4;
                
                for (let i = 1; i < points.length; i++) {
                    points[i].x += (points[i-1].x - points[i].x) * 0.35;
                    points[i].y += (points[i-1].y - points[i].y) * 0.35;
                }
                
                if (mouse.x !== -100) {
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';
                    
                    // Draw segment by segment to create a tapering effect
                    for (let i = 1; i < points.length - 1; i++) {
                        ctx.beginPath();
                        ctx.moveTo(points[i-1].x, points[i-1].y);
                        
                        const xc = (points[i].x + points[i + 1].x) / 2;
                        const yc = (points[i].y + points[i + 1].y) / 2;
                        ctx.quadraticCurveTo(points[i].x, points[i].y, xc, yc);
                        
                        // Tapering width and opacity (tail is thinner and more transparent)
                        const progress = 1 - (i / points.length);
                        ctx.lineWidth = progress * 2.5; 
                        ctx.strokeStyle = `rgba(43, 31, 23, ${progress * 0.9})`; 
                        ctx.stroke();
                    }
                }
                
                requestAnimationFrame(draw);
            }
            draw();
        });

        // Star Trail for Dark Universe Section
        document.addEventListener("DOMContentLoaded", () => {
            const container = document.getElementById('dark-universe');
            if (!container) return;
            
            const canvas = document.createElement('canvas');
            canvas.style.position = 'absolute';
            canvas.style.top = '0';
            canvas.style.left = '0';
            canvas.style.pointerEvents = 'none';
            canvas.style.zIndex = '30';
            container.appendChild(canvas);
            const ctx = canvas.getContext('2d');
            
            let width = container.offsetWidth;
            let height = container.offsetHeight;
            canvas.width = width;
            canvas.height = height;
            
            window.addEventListener('resize', () => {
                width = container.offsetWidth;
                height = container.offsetHeight;
                canvas.width = width;
                canvas.height = height;
            });

            const particles = [];
            
            window.addEventListener('mousemove', (e) => {
                const rect = container.getBoundingClientRect();
                // Check if mouse is within this section visually
                if (e.clientY >= rect.top && e.clientY <= rect.bottom) {
                    // Coordinates relative to the container
                    const localY = e.pageY - container.offsetTop;
                    const localX = e.pageX - container.offsetLeft;
                    
                    // Spawn a star
                    if (Math.random() > 0.4) {
                        particles.push({
                            x: localX + (Math.random() - 0.5) * 20,
                            y: localY + (Math.random() - 0.5) * 20,
                            size: Math.random() * 3.5 + 2,
                            life: 1.0,
                            vx: (Math.random() - 0.5) * 1.5,
                            vy: (Math.random() - 0.5) * 1.5 + 0.3
                        });
                    }
                }
            });

            // Supernova click burst
            window.addEventListener('click', (e) => {
                const rect = container.getBoundingClientRect();
                if (e.clientY >= rect.top && e.clientY <= rect.bottom) {
                    const localY = e.pageY - container.offsetTop;
                    const localX = e.pageX - container.offsetLeft;
                    
                    // Burst of stars (balanced size and count)
                    const burstCount = 10 + Math.random() * 5;
                    for (let i = 0; i < burstCount; i++) {
                        const angle = Math.random() * Math.PI * 2;
                        const speed = 0.5 + Math.random() * 1.5; 
                        particles.push({
                            x: localX,
                            y: localY,
                            size: Math.random() * 3.0 + 2.0, // Medium stars
                            life: 1.0 + Math.random() * 0.5, // Standard life
                            vx: Math.cos(angle) * speed,
                            vy: Math.sin(angle) * speed
                        });
                    }
                }
            });

            function drawStars() {
                ctx.clearRect(0, 0, width, height);
                
                for (let i = particles.length - 1; i >= 0; i--) {
                    const p = particles[i];
                    p.x += p.vx;
                    p.y += p.vy;
                    p.life -= 0.007; // Fades much slower now
                    
                    if (p.life <= 0) {
                        particles.splice(i, 1);
                        continue;
                    }
                    
                    // Inner bright core
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.size * p.life, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(255, 255, 255, ${p.life})`;
                    ctx.fill();
                    
                    // Hardware-accelerated fake glow (thinner as requested)
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, (p.size * p.life) * 1.6, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(255, 255, 255, ${p.life * 0.15})`;
                    ctx.fill();
                }
                
                if (isVisible) {
                    requestAnimationFrame(drawStars);
                }
            }

            // Performance Optimization: Only run animation when canvas is visible
            let isVisible = false;
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const wasVisible = isVisible;
                    isVisible = entry.isIntersecting;
                    if (isVisible && !wasVisible) {
                        drawStars(); // Kickstart the loop
                    }
                });
            });
            observer.observe(canvas);
        });

        // Leaf Burst Effect
        document.addEventListener('DOMContentLoaded', () => {
            window.addEventListener('click', (e) => {
                // Do not burst leaves over the black sections (already has stars)
                if (e.target && e.target.closest && (e.target.closest('footer') || e.target.closest('#dark-universe'))) return;
                
                const colors = ['bg-brand-200/80', 'bg-secondary-200/80', 'bg-brand-10/80', 'bg-secondary-100/80'];
                const numLeaves = 8 + Math.floor(Math.random() * 6); // Reduced number
                
                for(let i = 0; i < numLeaves; i++) {
                    const leaf = document.createElement('div');
                    const color = colors[Math.floor(Math.random() * colors.length)];
                    
                    // Thinner, smaller leaves with slight transparency
                    leaf.className = `fixed pointer-events-none w-2 h-2 md:w-3 md:h-3 rounded-tr-full rounded-bl-full shadow-sm z-[9999] ${color}`;
                    
                    // Optional: make them slightly elongated
                    leaf.style.transformOrigin = 'center';
                    
                    leaf.style.left = e.clientX + 'px';
                    leaf.style.top = e.clientY + 'px';
                    
                    document.body.appendChild(leaf);
                    
                    // Calculate burst physics
                    const angle = Math.random() * Math.PI * 2;
                    const velocity = 50 + Math.random() * 100;
                    const destX = Math.cos(angle) * velocity;
                    const destY = Math.sin(angle) * velocity + 100; // gravity
                    const rot = (Math.random() - 0.5) * 1080;
                    
                    const animation = leaf.animate([
                        { transform: 'translate(-50%, -50%) rotate(0deg) scale(0)', opacity: 1 },
                        { transform: `translate(-50%, -50%) rotate(${rot / 4}deg) scale(1) scaleY(1.5)`, opacity: 1, offset: 0.2 },
                        { transform: `translate(calc(-50% + ${destX}px), calc(-50% + ${destY}px)) rotate(${rot}deg) scale(0.6) scaleY(1.5)`, opacity: 0 }
                    ], {
                        duration: 2500 + Math.random() * 1500, // Slowed down significantly
                        easing: 'cubic-bezier(0.25, 1, 0.5, 1)',
                        fill: 'forwards'
                    });
                    
                    animation.onfinish = () => leaf.remove();
                }
            });
        });

        // Leaf Trail Effect (for bottom white sections)
        document.addEventListener('DOMContentLoaded', () => {
            let lastLeafX = 0;
            let lastLeafY = 0;
            
            window.addEventListener('mousemove', (e) => {
                if (!e.target || !e.target.closest) return;
                
                // Only spawn in bottom white sections (not hero, not dark-universe, not footer)
                if (e.target.closest('#hero') || e.target.closest('#dark-universe') || e.target.closest('footer')) return;
                
                const dist = Math.hypot(e.clientX - lastLeafX, e.clientY - lastLeafY);
                if (dist > 15) { // Spawn interval (denser)
                    spawnTrailLeaf(e.clientX, e.clientY);
                    lastLeafX = e.clientX;
                    lastLeafY = e.clientY;
                }
            });
            
            function spawnTrailLeaf(x, y) {
                const leaf = document.createElement('div');
                const colors = ['bg-brand-200/75', 'bg-secondary-200/75', 'bg-brand-10/75'];
                const color = colors[Math.floor(Math.random() * colors.length)];
                
                leaf.className = `fixed pointer-events-none w-3 h-3 md:w-4 md:h-4 rounded-tr-full rounded-bl-full shadow-sm z-[9998] ${color}`;
                leaf.style.left = x + 'px';
                leaf.style.top = y + 'px';
                
                document.body.appendChild(leaf);
                
                const rot = (Math.random() - 0.5) * 360;
                const destX = (Math.random() - 0.5) * 40;
                const destY = 20 + Math.random() * 30;
                
                const animation = leaf.animate([
                    { transform: 'translate(-50%, -50%) rotate(0deg) scale(0.9)', opacity: 0.9 },
                    { transform: `translate(calc(-50% + ${destX}px), calc(-50% + ${destY}px)) rotate(${rot}deg) scale(0.4)`, opacity: 0 }
                ], {
                    duration: 800 + Math.random() * 400,
                    easing: 'ease-out',
                    fill: 'forwards'
                });
                
                animation.onfinish = () => leaf.remove();
            }
        });

        // Coverflow Scale Effect for Carousel
        document.addEventListener('DOMContentLoaded', () => {
            const track = document.getElementById('scale-carousel-track');
            if (!track) return;
            
            const items = track.querySelectorAll('.carousel-scale-item');
            
            function updateScales() {
                const screenCenter = window.innerWidth / 2;
                
                items.forEach(item => {
                    const rect = item.getBoundingClientRect();
                    const itemCenter = rect.left + rect.width / 2;
                    
                    // Calculate absolute distance from center of screen
                    const dist = Math.abs(screenCenter - itemCenter);
                    
                    // We scale down the further away it is from center
                    // Max distance is roughly 70% of screen width before it hits min scale
                    const maxDist = window.innerWidth * 0.7; 
                    
                    // At center (dist=0), scale is 1.0. 
                    // At edges (dist=maxDist), scale drops to 0.7.
                    let scale = 1.0 - (dist / maxDist) * 0.3;
                    
                    // Clamp scale between 0.7 and 1.0
                    scale = Math.max(0.7, Math.min(1.0, scale));
                    
                    // Optional: Fade out slightly when smaller (uncomment if desired)
                    // const opacity = Math.max(0.5, scale);
                    // item.style.opacity = opacity;
                    
                    item.style.transform = `scale(${scale})`;
                });
                
                if (isVisible) {
                    requestAnimationFrame(updateScales);
                }
            }
            
            // Performance Optimization: Only calculate layout if carousel is visible
            let isVisible = false;
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const wasVisible = isVisible;
                    isVisible = entry.isIntersecting;
                    if (isVisible && !wasVisible) {
                        updateScales();
                    }
                });
            });
            observer.observe(track);
        });

        // Smart Navbar & Back to Top Button
        document.addEventListener('DOMContentLoaded', () => {
            const nav = document.getElementById('main-nav');
            const backToTop = document.getElementById('back-to-top');
            
            let lastScrollY = window.scrollY;
            let isScrollingDown = false;
            
            window.addEventListener('scroll', () => {
                // Back to top visibility
                if (window.scrollY > 500) {
                    if (backToTop) {
                        backToTop.classList.remove('translate-y-[150%]', 'opacity-0');
                    }
                } else {
                    if (backToTop) {
                        backToTop.classList.add('translate-y-[150%]', 'opacity-0');
                    }
                }

                if (!nav) return;
                
                if (window.scrollY > lastScrollY && window.scrollY > 100) {
                    // Scrolling down: hide
                    isScrollingDown = true;
                    nav.classList.add('-translate-y-[150%]');
                } else {
                    // Scrolling up: show
                    isScrollingDown = false;
                    nav.classList.remove('-translate-y-[150%]');
                }
                lastScrollY = window.scrollY;
            }, { passive: true });

            // Show navbar when mouse hovers near the top of the screen, hide when leaves
            window.addEventListener('mousemove', (e) => {
                if (e.clientY <= 120) {
                    nav.classList.remove('-translate-y-[150%]');
                } else if (isScrollingDown && window.scrollY > 100) {
                    nav.classList.add('-translate-y-[150%]');
                }
            });
        });
    </script>

    <script>
        // Dynamic 3D Card Tilt Effect
        document.addEventListener('DOMContentLoaded', () => {
            const containers = document.querySelectorAll('.tilt-container');
            
            containers.forEach(container => {
                const element = container.querySelector('.tilt-element');
                
                container.addEventListener('mousemove', (e) => {
                    const rect = container.getBoundingClientRect();
                    const x = e.clientX - rect.left; 
                    const y = e.clientY - rect.top;  
                    
                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;
                    
                    // Calculate tilt based on mouse distance from center
                    // Pushes the hovered area INWARDS
                    const rotateX = ((centerY - y) / centerY) * 18; 
                    const rotateY = ((x - centerX) / centerX) * 18;
                    
                    element.style.transform = `scale(1.08) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
                });
                
                container.addEventListener('mouseleave', () => {
                    // Reset to flat when mouse leaves
                    element.style.transform = `scale(1) rotateX(0deg) rotateY(0deg)`;
                });
            });
        });

        // Dynamic Books Wave Effect
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('books-container');
            if (!container) return;
            
            const items = container.querySelectorAll('.book-item');
            
            const booksData = Array.from(items).map(book => ({
                el: book,
                currentLift: 0,
                targetLift: 0
            }));
            
            let isHovering = false;
            let mouseX = 0;
            const radius = 220; 
            
            container.addEventListener('mouseenter', () => {
                isHovering = true;
            });
            
            container.addEventListener('mousemove', (e) => {
                mouseX = e.clientX;
            });
            
            container.addEventListener('mouseleave', () => {
                isHovering = false;
            });
            
            function animateBooks() {
                booksData.forEach(data => {
                    if (isHovering) {
                        const rect = data.el.getBoundingClientRect();
                        const bookCenterX = rect.left + rect.width / 2;
                        const dist = Math.abs(mouseX - bookCenterX);
                        
                        if (dist < radius) {
                            const intensity = Math.cos((dist / radius) * (Math.PI / 2));
                            data.targetLift = intensity * 40; // Increased lift for a more pronounced wave
                        } else {
                            data.targetLift = 0;
                        }
                    } else {
                        data.targetLift = 0;
                    }
                    
                    // Smooth linear interpolation (lerp)
                    data.currentLift += (data.targetLift - data.currentLift) * 0.08;
                    
                    // Apply transform
                    if (Math.abs(data.targetLift - data.currentLift) > 0.1 || data.currentLift > 0.1) {
                        data.el.style.transform = `translateY(-${data.currentLift}px)`;
                    } else if (data.currentLift <= 0.1 && data.el.style.transform !== 'translateY(0px)') {
                        data.currentLift = 0;
                        data.el.style.transform = 'translateY(0px)';
                    }
                });
                
                requestAnimationFrame(animateBooks);
            }
            
            animateBooks();
        });
    </script>

    <script>
        // Parallax Depth Effect
        document.addEventListener('DOMContentLoaded', () => {
            const parallaxWraps = document.querySelectorAll('.parallax-wrap');
            let scrollY = window.scrollY;
            let currentY = window.scrollY;
            
            window.addEventListener('scroll', () => {
                scrollY = window.scrollY;
            }, { passive: true });
            
            function animateParallax() {
                // Only run heavy DOM calculations if the page has actually scrolled
                if (Math.abs(scrollY - currentY) > 0.1) {
                    // Smooth interpolation (lerp)
                    currentY += (scrollY - currentY) * 0.1;
                    
                    parallaxWraps.forEach(wrap => {
                        const speed = parseFloat(wrap.dataset.speed || 0.4) * 1.5; 
                        const parent = wrap.parentElement;
                        if (!parent) return;
                        
                        const rect = parent.getBoundingClientRect();
                        
                        if (rect.bottom > -200 && rect.top < window.innerHeight + 200) {
                            const smoothTop = rect.top + (scrollY - currentY);
                            const yPos = -(smoothTop * speed);
                            wrap.style.transform = `translate3d(0, ${yPos}px, 0)`;
                        }
                    });
                }
                requestAnimationFrame(animateParallax);
            }
            animateParallax();
        });
    </script>


    <script>
        // True Scroll-Jacking (Section-by-Section)
        document.addEventListener('DOMContentLoaded', () => {
            const sections = Array.from(document.querySelectorAll('header#hero, section, footer'));
            let isAnimating = false;
            
            window.addEventListener('wheel', (e) => {
                // Prevent default native scroll
                e.preventDefault();
                
                if (isAnimating) return;
                
                let currentIdx = -1;
                let minDistance = Infinity;
                
                // Find which section we are currently focusing on
                sections.forEach((sec, idx) => {
                    const rect = sec.getBoundingClientRect();
                    const distance = Math.abs(rect.top);
                    if (distance < minDistance) {
                        minDistance = distance;
                        currentIdx = idx;
                    }
                });
                
                let nextIdx = currentIdx;
                
                // Determine direction
                // e.deltaY > 0 means scrolling down
                if (e.deltaY > 20 || e.deltaY < -20) {
                    if (e.deltaY > 0) {
                        nextIdx = Math.min(sections.length - 1, currentIdx + 1);
                    } else {
                        nextIdx = Math.max(0, currentIdx - 1);
                    }
                    
                    if (nextIdx !== currentIdx) {
                        isAnimating = true;
                        
                        const targetSection = sections[nextIdx];
                        const targetY = window.scrollY + targetSection.getBoundingClientRect().top;
                        
                        window.scrollTo({
                            top: targetY,
                            behavior: 'smooth' // Let the browser handle the buttery smooth slide
                        });
                        
                        // Prevent multiple triggers while the browser is scrolling (roughly 800ms)
                        setTimeout(() => {
                            isAnimating = false;
                        }, 900);
                    }
                }
            }, { passive: false });

            // Intercept anchor links to match scroll jack calculation exactly
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', (e) => {
                    const targetId = anchor.getAttribute('href');
                    if (targetId === '#') return;
                    
                    const targetSection = document.querySelector(targetId);
                    if (targetSection) {
                        e.preventDefault();
                        if (isAnimating) return;
                        
                        isAnimating = true;
                        const targetY = window.scrollY + targetSection.getBoundingClientRect().top;
                        
                        window.scrollTo({
                            top: targetY,
                            behavior: 'smooth'
                        });
                        
                        setTimeout(() => {
                            isAnimating = false;
                        }, 900);
                    }
                });
            });
            
            // Allow normal keyboard navigation (arrows/space) but with smooth scroll
            window.addEventListener('keydown', (e) => {
                if (['ArrowUp', 'ArrowDown', 'Space'].includes(e.code)) {
                    // Optional: You could also intercept keyboard for section-by-section,
                    // but leaving it default gives a fallback for accessibility.
                }
            });
        });
    </script>

    <script>
    // 3D Particle Galaxy with Magnetic Repel
    document.addEventListener("DOMContentLoaded", () => {
        const canvas = document.getElementById('galaxy-particle-canvas');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        function resizeCanvas() {
            const rect = canvas.parentElement.getBoundingClientRect();
            canvas.width = rect.width;
            canvas.height = rect.height;
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        const img = new Image();
        img.src = "{{ $img('galaxy-spiral.png') }}";
        img.onload = () => {
            const offCanvas = document.createElement('canvas');
            const size = 250; // Significantly higher resolution for a much denser particle count
            offCanvas.width = size;
            const aspect = img.height / (img.width || 1);
            offCanvas.height = size * aspect;
            
            const offCtx = offCanvas.getContext('2d', { willReadFrequently: true });
            offCtx.drawImage(img, 0, 0, offCanvas.width, offCanvas.height);
            const imgData = offCtx.getImageData(0, 0, offCanvas.width, offCanvas.height).data;

            const particles = [];
            for (let y = 0; y < offCanvas.height; y++) {
                for (let x = 0; x < offCanvas.width; x++) {
                    const index = (y * offCanvas.width + x) * 4;
                    const a = imgData[index + 3];
                    
                    // Probabilistic spawning: bright pixels (arms) are dense, faint pixels (gaps) are sparse.
                    // Lowered strictness to allow much more stardust to spawn overall.
                    const brightness = a / 255;
                    if (brightness > 0.03 && Math.random() < Math.pow(brightness, 1.1)) { 
                        const nx = (x / offCanvas.width) - 0.5;
                        const ny = (y / offCanvas.height) - 0.5;
                        const dist = Math.sqrt(nx*nx + ny*ny);
                        
                        // Thinner Z spread so the spiral arms don't blur into a cloud
                        const zSpread = 70 * Math.max(0, 0.5 - dist);
                        
                        particles.push({
                            baseX: nx * 620, // Slightly larger base spread
                            baseY: ny * 620,
                            z: (Math.random() - 0.5) * zSpread,
                            // Boost opacity slightly so the fine dust is highly visible
                            color: `rgba(${imgData[index]}, ${imgData[index+1]}, ${imgData[index+2]}, ${Math.min(1, brightness * 2.0)})`,
                            baseSize: Math.random() * 1.8 + 1.2, // Finer stardust
                            offsetX: 0,
                            offsetY: 0,
                            vx: 0,
                            vy: 0
                        });
                    }
                }
            }

            let spinAngle = 0;
            let wobbleAngle = 0;
            
            // Coin tilt relative to the "floor" - reduced for less extreme wobble
            const tiltAngle = 5 * Math.PI / 180; 
            const cosTilt = Math.cos(tiltAngle);
            const sinTilt = Math.sin(tiltAngle);

            // Camera angle looking down at the floor (closer to 0 makes it flatter on the floor)
            const camTilt = -18 * Math.PI / 180; 
            const cosCam = Math.cos(camTilt);
            const sinCam = Math.sin(camTilt);

            let mouseX = -1000;
            let mouseY = -1000;
            let isHovering = false;
            
            window.addEventListener('mousemove', (e) => {
                const rect = canvas.getBoundingClientRect();
                mouseX = e.clientX - rect.left;
                mouseY = e.clientY - rect.top;
                
                // Check if mouse is within the general area of the galaxy
                // The visual center of the galaxy is offset to 68% of the canvas width
                const dx = mouseX - rect.width * 0.68;
                const dy = mouseY - rect.height / 2;
                if (dx * dx + dy * dy < 350 * 350) {
                    isHovering = true;
                } else {
                    isHovering = false;
                }
            });
            
            // Handle mouse leaving the window entirely
            window.addEventListener('mouseout', (e) => {
                if (!e.relatedTarget) {
                    isHovering = false;
                    mouseX = -1000;
                    mouseY = -1000;
                }
            });

            const baseSpinSpeed = -0.0003;
            const baseWobbleSpeed = 0.002;

            function animate() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                
                // Keep the rotation dynamic (never stop)
                spinAngle += baseSpinSpeed;
                wobbleAngle += baseWobbleSpeed;

                const cosS = Math.cos(spinAngle);
                const sinS = Math.sin(spinAngle);
                const cosW = Math.cos(wobbleAngle);
                const sinW = Math.sin(wobbleAngle);

                // Offset the center to the right side of the canvas
                // So the default galaxy stays away from text, but particles can fly into text on the left
                const cx = canvas.width * 0.68; 
                const cy = canvas.height / 2;

                for (let i = 0; i < particles.length; i++) {
                    const p = particles[i];

                    // 1. Spin in its own 2D plane
                    const sX = p.baseX * cosS - p.baseY * sinS;
                    const sY = p.baseX * sinS + p.baseY * cosS;
                    const sZ = p.z; // Thickness

                    // 2. Map to 3D floor (X is horizontal, Y is vertical, Z is depth)
                    // The galaxy lies flat on the XZ plane.
                    const fX = sX;
                    const fY = sZ; 
                    const fZ = sY;

                    // 3. Tilt the disk slightly from the floor (almost falling)
                    const tX = fX;
                    const tY = fY * cosTilt - fZ * sinTilt;
                    const tZ = fY * sinTilt + fZ * cosTilt;

                    // 4. Wobble around the vertical Y axis
                    const wX = tX * cosW - tZ * sinW;
                    const wY = tY;
                    const wZ = tX * sinW + tZ * cosW;

                    // 5. Camera tilt (looking down at the floor)
                    const cX = wX;
                    const cY = wY * cosCam - wZ * sinCam;
                    const cZ = wY * sinCam + wZ * cosCam;

                    // 6. Perspective projection
                    const fov = 900;
                    const scale = fov / (fov + cZ);
                    
                    let screenX = cx + cX * scale;
                    let screenY = cy + cY * scale;

                    // 7. Magnetic Repel (Interactive Scatter)
                    if (isHovering) {
                        const dx = screenX - mouseX;
                        const dy = screenY - mouseY;
                        const distSq = dx * dx + dy * dy;
                        const repelRadius = 120; // Increased radius for better sensitivity
                        
                        if (distSq < repelRadius * repelRadius && distSq > 0) {
                            const dist = Math.sqrt(distSq);
                            // Smoother falloff for better response without being too explosive
                            const force = Math.pow((repelRadius - dist) / repelRadius, 1.5);
                            
                            p.vx += (dx / dist) * force * 1.2; // Slowed down pushing force
                            p.vy += (dy / dist) * force * 1.2;
                        }
                        
                        // Higher friction when scattering makes particles feel heavier and slide less
                        p.vx *= 0.82;
                        p.vy *= 0.82;
                        
                        p.offsetX += p.vx;
                        p.offsetY += p.vy;
                    } else {
                        // Return to original position smoothly and very slowly (No bounce!)
                        p.offsetX += (0 - p.offsetX) * 0.025;
                        p.offsetY += (0 - p.offsetY) * 0.025;
                        
                        // Clear velocity so momentum doesn't cause a bounce later
                        p.vx = 0;
                        p.vy = 0;
                    }

                    // Draw
                    ctx.fillStyle = p.color;
                    const size = p.baseSize * scale;
                    ctx.fillRect(screenX + p.offsetX, screenY + p.offsetY, size, size);
                }

                if (isVisible) {
                    requestAnimationFrame(animate);
                }
            }
            
            // Performance Optimization: Pause galaxy rendering when off-screen
            let isVisible = false;
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const wasVisible = isVisible;
                    isVisible = entry.isIntersecting;
                    if (isVisible && !wasVisible) {
                        animate();
                    }
                });
            });
            observer.observe(canvas);
        };
    });
    </script>
</body>
</html>
