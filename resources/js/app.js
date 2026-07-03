import collapse from '@alpinejs/collapse';
import './whiteboard';

// ---- Komponen carousel (From Writers To Writers) ----
document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(collapse);
    Alpine.data('carousel', () => ({
        active: 0,
        slides: 0,
        init() {
            this.slides = this.$refs.track.children.length;
            this.$refs.track.addEventListener('scroll', () => {
                const w = this.$refs.track.children[0]?.offsetWidth || 1;
                this.active = Math.round(this.$refs.track.scrollLeft / (w + 24));
            }, { passive: true });
        },
        goTo(i) {
            const child = this.$refs.track.children[i];
            if (child) this.$refs.track.scrollTo({ left: child.offsetLeft - 16, behavior: 'smooth' });
        },
        next() { this.goTo(Math.min(this.active + 1, this.slides - 1)); },
        prev() { this.goTo(Math.max(this.active - 1, 0)); },
    }));
})

// ---- Scroll-reveal halus untuk landing page ----
function initReveal() {
    const els = document.querySelectorAll('.reveal');
    if (!els.length) return;

    if (!('IntersectionObserver' in window)) {
        els.forEach((el) => el.classList.add('in-view'));
        return;
    }

    const io = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                    io.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.15, rootMargin: '0px 0px -8% 0px' }
    );

    els.forEach((el) => io.observe(el));
}

document.addEventListener('DOMContentLoaded', () => {
    initReveal();
});
