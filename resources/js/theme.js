document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
        isDark: false,
        init() {
            try {
                const stored = localStorage.getItem('theme');
                if (stored === 'dark') {
                    this.isDark = true;
                } else if (stored === 'light') {
                    this.isDark = false;
                } else {
                    this.isDark = document.documentElement.classList.contains('dark');
                }
                this.applyTheme(this.isDark, false);
            } catch (e) {}
        },
        toggle() {
            try {
                this.isDark = !this.isDark;
                this.applyTheme(this.isDark, true);
                return this.isDark;
            } catch (e) {
                return false;
            }
        },
        setTheme(themeName) {
            try {
                this.isDark = (themeName === 'dark');
                this.applyTheme(this.isDark, true);
            } catch (e) {}
        },
        applyTheme(isDark, saveToStorage = true) {
            try {
                this.isDark = isDark;
                if (isDark) {
                    document.documentElement.classList.add('dark');
                    if (saveToStorage) localStorage.setItem('theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    if (saveToStorage) localStorage.setItem('theme', 'light');
                }
                window.dispatchEvent(new CustomEvent('theme-changed', { detail: { isDark } }));
            } catch (e) {}
        }
    });
});

// Ensure SPA (wire:navigate) page transitions preserve theme class on root element
const syncDomTheme = () => {
    try {
        const stored = localStorage.getItem('theme');
        if (stored === 'dark') {
            document.documentElement.classList.add('dark');
        } else if (stored === 'light') {
            document.documentElement.classList.remove('dark');
        }
    } catch (e) {}
};

document.addEventListener('livewire:navigating', syncDomTheme);
document.addEventListener('livewire:navigated', syncDomTheme);

