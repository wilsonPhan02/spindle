document.addEventListener('alpine:init', () => {
    Alpine.store('layout', {
        isPinned: localStorage.getItem('sidebarPinned') !== null ? localStorage.getItem('sidebarPinned') === 'true' : true,
        isHovered: false,
        togglePin(val) {
            this.isPinned = val !== undefined ? val : !this.isPinned;
            localStorage.setItem('sidebarPinned', this.isPinned);
        }
    });
});

// Global SPA state freshness logic
function checkStaleData() {
    const lastMutation = localStorage.getItem('spindle_last_mutation');
    const pageLoadTime = parseFloat(document.body.getAttribute('data-load-time')) * 1000;
    
    if (lastMutation && pageLoadTime && parseInt(lastMutation) > pageLoadTime) {
        window.location.reload();
    }
}

document.addEventListener('livewire:initialized', () => {
    Livewire.hook('commit', ({ succeed }) => {
        succeed(() => {
            const now = Date.now();
            localStorage.setItem('spindle_last_mutation', now.toString());
            document.body.setAttribute('data-load-time', (now / 1000).toString());
        });
    });
});

document.addEventListener('livewire:navigated', checkStaleData);
window.addEventListener('pageshow', (e) => {
    if (e.persisted) checkStaleData();
});

// Guard: watch for dark class being removed by morphdom and immediately re-add it
const themeObserver = new MutationObserver(() => {
    try {
        const stored = localStorage.getItem('theme');
        if (stored === 'dark' && !document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.add('dark');
        }
    } catch (e) {}
});
themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
