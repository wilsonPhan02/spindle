import re

with open('welcome.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. mtn-peak2
content = content.replace(
'''        <img src="{{ $img('mtn-peak2.png') }}" alt="" class="pointer-events-none absolute z-[1] max-w-none select-none"
             style="bottom: -10%; left: 5%; width: 95%;">''',
'''        <div class="pointer-events-none absolute inset-0 z-[1] parallax-wrap" data-speed="0.3">
            <img src="{{ $img('mtn-peak2.png') }}" alt="" class="absolute max-w-none select-none"
                 style="bottom: -10%; left: 5%; width: 95%;">
        </div>'''
)

# 2. mtn-range
content = content.replace(
'''        <img src="{{ $img('mtn-range.png') }}" alt="" class="pointer-events-none absolute z-[2] max-w-none select-none"
             style="bottom: -5%; left: -2%; width: 106%;">''',
'''        <div class="pointer-events-none absolute inset-0 z-[2] parallax-wrap" data-speed="0.2">
            <img src="{{ $img('mtn-range.png') }}" alt="" class="absolute max-w-none select-none"
                 style="bottom: -5%; left: -2%; width: 106%;">
        </div>'''
)

# 3. mountains-dark
content = content.replace(
'''        <img src="{{ $img('mountains-dark.png') }}" alt=""
             class="pointer-events-none absolute inset-x-[-22%] bottom-[-1px] z-[4] w-[144%] max-w-none select-none">''',
'''        <div class="pointer-events-none absolute inset-0 z-[4] parallax-wrap" data-speed="0.1">
            <img src="{{ $img('mountains-dark.png') }}" alt=""
                 class="absolute inset-x-[-22%] bottom-[-1px] w-[144%] max-w-none select-none">
        </div>'''
)

# 4. galaxy-spiral
content = content.replace(
'''            <div class="pointer-events-none absolute right-[4%] top-1/2 z-0 h-[520px] w-[520px] max-w-[86%] -translate-y-1/2">
                <img src="{{ $img('galaxy-spiral.png') }}" alt=""
                     class="animate-spin-slow h-full w-full object-contain opacity-80" style="animation-duration: 220s;">
            </div>''',
'''            <div class="pointer-events-none absolute inset-0 z-0 parallax-wrap" data-speed="0.4">
                <div class="absolute right-[4%] top-1/2 h-[520px] w-[520px] max-w-[86%] -translate-y-1/2">
                    <img src="{{ $img('galaxy-spiral.png') }}" alt=""
                         class="animate-spin-slow h-full w-full object-contain opacity-80" style="animation-duration: 220s;">
                </div>
            </div>'''
)

# Inject JS for Parallax
parallax_js = '''
        // Parallax Depth Effect
        document.addEventListener('DOMContentLoaded', () => {
            const parallaxWraps = document.querySelectorAll('.parallax-wrap');
            let scrollY = window.scrollY;
            let currentY = window.scrollY;
            
            window.addEventListener('scroll', () => {
                scrollY = window.scrollY;
            }, { passive: true });
            
            function animateParallax() {
                // Smooth interpolation (lerp)
                currentY += (scrollY - currentY) * 0.1;
                
                parallaxWraps.forEach(wrap => {
                    const speed = parseFloat(wrap.dataset.speed || 0.2);
                    const parent = wrap.parentElement;
                    if (!parent) return;
                    
                    const rect = parent.getBoundingClientRect();
                    // If parent is visible
                    if (rect.bottom > 0 && rect.top < window.innerHeight) {
                        // Move background in the opposite direction of scroll to make it slower
                        const yPos = -(rect.top * speed);
                        wrap.style.transform = `translate3d(0, ${yPos}px, 0)`;
                    }
                });
                requestAnimationFrame(animateParallax);
            }
            animateParallax();
        });
'''

# insert JS before closing body
if '// Parallax Depth Effect' not in content:
    content = content.replace('</body>', parallax_js + '\n</body>')

with open('welcome.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)
