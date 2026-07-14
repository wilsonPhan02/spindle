document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('hero')) return;
    
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
});
        
        // Elegant Ribbon/Thread Cursor Trail
        document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('hero')) return;
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
        document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('hero')) return;
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
    if (!document.getElementById('hero')) return;
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
    if (!document.getElementById('hero')) return;
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
    if (!document.getElementById('hero')) return;
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
    if (!document.getElementById('hero')) return;
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
                if (e.clientY <= 120 || (e.target && e.target.closest('#main-nav'))) {
                    nav.classList.remove('-translate-y-[150%]');
                } else if (isScrollingDown && window.scrollY > 100) {
                    nav.classList.add('-translate-y-[150%]');
                }
            });
        });

// Dynamic 3D Card Tilt Effect
        document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('hero')) return;
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
    if (!document.getElementById('hero')) return;
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

// Parallax Depth Effect
        document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('hero')) return;
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

// True Scroll-Jacking (Section-by-Section)
        document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('hero')) return;
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

// 3D Particle Galaxy with Magnetic Repel
    document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('hero')) return;
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
        img.src = "/images/landing/galaxy-spiral.png";
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

// Force animations to replay when navigating 'Back' to this page (BFCache fix)
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                // If loaded from back/forward cache, force a fresh reload to replay all initial CSS/JS animations
                window.location.reload();
            }
        });

