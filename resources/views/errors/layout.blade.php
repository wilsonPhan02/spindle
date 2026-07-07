<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - Spindle</title>
    
    <link rel="icon" href="/favicon.png?v=5" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-montserrat antialiased min-h-screen flex items-center justify-center relative overflow-hidden" style="background-color: #2a1f17;">
    <!-- Starfield Background -->
    @include('partials.starfield')
    
    <div class="relative z-10 text-center px-6 max-w-2xl mx-auto flex flex-col items-center mt-20 md:mt-0">
        
        <!-- Abstract Irregular Circles (Lingkaran Tak Beraturan) -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 pointer-events-none z-[-1] flex items-center justify-center">
            <!-- Inner Ring -->
            <div class="absolute w-[500px] h-[500px] md:w-[700px] md:h-[700px] border-[2px] border-solid border-[#D4AF37] opacity-50 animate-spin" style="animation-duration: 35s; border-radius: 45% 55% 52% 48% / 54% 46% 49% 51%; filter: drop-shadow(0 0 10px rgba(212,175,55,0.4));"></div>
            <!-- Middle Ring -->
            <div class="absolute w-[700px] h-[700px] md:w-[1000px] md:h-[1000px] border-[3px] border-solid border-[#F5EFE9] opacity-30 animate-spin" style="animation-duration: 45s; animation-direction: reverse; border-radius: 53% 47% 45% 55% / 48% 52% 55% 45%; filter: drop-shadow(0 0 12px rgba(245,239,233,0.5));"></div>
            <!-- Outer Ring -->
            <div class="absolute w-[900px] h-[900px] md:w-[1300px] md:h-[1300px] border-[2px] border-solid border-[#D4AF37] opacity-60 animate-spin" style="animation-duration: 25s; border-radius: 46% 54% 55% 45% / 55% 45% 47% 53%; filter: drop-shadow(0 0 8px rgba(212,175,55,0.5));"></div>
        </div>

        <!-- Error Number -->
        <div class="text-7xl md:text-8xl font-merriweather font-bold leading-none mb-6 drop-shadow-md select-none" style="color: #A27B5C; text-shadow: 0 2px 10px rgba(0,0,0,0.3);">
            @yield('code')
        </div>
        
        <!-- Narrative Heading -->
        <h1 class="text-2xl md:text-3xl font-merriweather mb-6 drop-shadow-sm" style="color: #F5EFE9;">
            @yield('heading')
        </h1>
        
        <!-- Narrative Description -->
        <p class="text-sm md:text-base mb-10 leading-relaxed md:px-10" style="color: #C4B7A3;">
            @yield('description')
        </p>
        
        <!-- Call to Action -->
        <a href="{{ Auth::check() ? route('dashboard') : url('/') }}" 
           class="inline-flex items-center rounded-full bg-secondary-150 px-9 py-3.5 font-merriweather text-[15px] text-brand-10 shadow-md transition-all duration-300 hover:bg-secondary-200 hover:-translate-y-1 hover:shadow-lg focus:outline-none">
            Return to Safety
        </a>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const container = document.body;
            
            const canvas = document.createElement('canvas');
            canvas.style.position = 'absolute';
            canvas.style.top = '0';
            canvas.style.left = '0';
            canvas.style.pointerEvents = 'none';
            canvas.style.zIndex = '50'; // Ensure it's above everything
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

            // --- Mouse Cursor Particles ---
            const particles = [];
            
            window.addEventListener('mousemove', (e) => {
                const localY = e.pageY - container.offsetTop;
                const localX = e.pageX - container.offsetLeft;
                
                if (Math.random() > 0.3) {
                    particles.push({
                        x: localX + (Math.random() - 0.5) * 20,
                        y: localY + (Math.random() - 0.5) * 20,
                        size: Math.random() * 3.5 + 2,
                        life: 1.0,
                        vx: (Math.random() - 0.5) * 1.5,
                        vy: (Math.random() - 0.5) * 1.5 + 0.3
                    });
                }
            });

            window.addEventListener('click', (e) => {
                const localY = e.pageY - container.offsetTop;
                const localX = e.pageX - container.offsetLeft;
                
                const burstCount = 15 + Math.random() * 10;
                for (let i = 0; i < burstCount; i++) {
                    const angle = Math.random() * Math.PI * 2;
                    const speed = 0.5 + Math.random() * 2.0; 
                    particles.push({
                        x: localX,
                        y: localY,
                        size: Math.random() * 3.0 + 2.0,
                        life: 1.0 + Math.random() * 0.5,
                        vx: Math.cos(angle) * speed,
                        vy: Math.sin(angle) * speed
                    });
                }
            });

            function drawParticles() {
                ctx.clearRect(0, 0, width, height);
                ctx.globalCompositeOperation = 'screen';
                
                for (let i = particles.length - 1; i >= 0; i--) {
                    const p = particles[i];
                    p.x += p.vx;
                    p.y += p.vy;
                    p.life -= 0.007; 
                    
                    if (p.life <= 0) {
                        particles.splice(i, 1);
                        continue;
                    }
                    
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.size * p.life, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(255, 255, 255, ${p.life})`;
                    ctx.fill();
                    
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, (p.size * p.life) * 1.6, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(255, 255, 255, ${p.life * 0.2})`; 
                    ctx.fill();
                }
                
                requestAnimationFrame(drawParticles);
            }
            drawParticles();
        });
    </script>
</body>
</html>
