// Scroll-reveal halus untuk landing page.
// Setiap elemen ber-class `.reveal` akan mendapat `.in-view` saat masuk viewport.
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

// Navbar berubah solid setelah scroll sedikit melewati hero.
function initNavbar() {
    const nav = document.getElementById('site-nav');
    if (!nav) return;
    const onScroll = () => {
        if (window.scrollY > 40) nav.classList.add('nav-scrolled');
        else nav.classList.remove('nav-scrolled');
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
}

document.addEventListener('DOMContentLoaded', () => {
    initReveal();
    initNavbar();
});
