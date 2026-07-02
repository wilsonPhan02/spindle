import re

with open('welcome.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Replace ribbon logic
ribbon_target = '''                // Do not show thread cursor over the black sections (dark-universe or footer)
                if (e.target && e.target.closest && (e.target.closest('footer') || e.target.closest('#dark-universe'))) {'''
ribbon_new = '''                // Only show thread cursor over the hero section
                if (e.target && e.target.closest && (!e.target.closest('#hero'))) {'''
content = content.replace(ribbon_target, ribbon_new)

# 2. Add leaf trail after leaf burst
burst_target = '''                    animation.onfinish = () => leaf.remove();
                }
            });
        });'''

trail_code = '''                    animation.onfinish = () => leaf.remove();
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
                if (dist > 35) { // Spawn interval
                    spawnTrailLeaf(e.clientX, e.clientY);
                    lastLeafX = e.clientX;
                    lastLeafY = e.clientY;
                }
            });
            
            function spawnTrailLeaf(x, y) {
                const leaf = document.createElement('div');
                const colors = ['bg-brand-200/50', 'bg-secondary-200/50', 'bg-brand-10/50'];
                const color = colors[Math.floor(Math.random() * colors.length)];
                
                leaf.className = ixed pointer-events-none w-2 h-2 rounded-tr-full rounded-bl-full shadow-sm z-[9998] ;
                leaf.style.left = x + 'px';
                leaf.style.top = y + 'px';
                
                document.body.appendChild(leaf);
                
                const rot = (Math.random() - 0.5) * 360;
                const destX = (Math.random() - 0.5) * 40;
                const destY = 20 + Math.random() * 30;
                
                const animation = leaf.animate([
                    { transform: 'translate(-50%, -50%) rotate(0deg) scale(0.6)', opacity: 0.7 },
                    { transform: 	ranslate(calc(-50% + px), calc(-50% + px)) rotate(deg) scale(0.2), opacity: 0 }
                ], {
                    duration: 800 + Math.random() * 400,
                    easing: 'ease-out',
                    fill: 'forwards'
                });
                
                animation.onfinish = () => leaf.remove();
            }
        });'''

content = content.replace(burst_target, trail_code)

with open('welcome.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)
