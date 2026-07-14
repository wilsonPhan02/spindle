<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ auth()->user()?->profile?->theme === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    {{-- This MUST be the very first script — blocking, no defer/async — to prevent Flash of Incorrect Theme (FOIT) --}}
    <script>
        (() => {
            try {
                const stored = localStorage.getItem('theme');
                const dbTheme = "{{ auth()->user()?->profile?->theme ?? '' }}";
                const isDark = stored ? stored === 'dark' : dbTheme === 'dark';
                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Spindle - Dashboard') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <!-- Cropper.js -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>
<body
    data-load-time="{{ microtime(true) }}"
    class="bg-bg-main text-text-100 antialiased min-h-screen flex overflow-hidden"
    x-data="{
        currentUsername: '{{ Auth::user()->profile?->username ?? explode('@', Auth::user()->email)[0] }}',
        currentAvatarUrl: '{{ Auth::user()->profile?->avatar_url ? Storage::url(Auth::user()->profile->avatar_url) : '' }}'
    }"
    @profile-updated.window="
        currentUsername = $event.detail.newName;
        currentAvatarUrl = $event.detail.avatarUrl;
    "
>
    @persist('sidebar')
        <livewire:layout.sidebar />
    @endpersist
    <div
        x-show="!$store.layout.isPinned"
        @mouseenter="$store.layout.isHovered = true"
        class="fixed inset-y-0 left-0 w-3 z-40 bg-transparent cursor-pointer"
    ></div>

    <button
        x-show="!$store.layout.isPinned && !$store.layout.isHovered"
        @click="$store.layout.togglePin(true)"
        x-transition.opacity
        class="fixed left-0 top-1/2 -translate-y-1/2 w-6 h-16 bg-brand-100 border border-subtext-70 border-l-0 rounded-r-full shadow-md z-50 flex items-center justify-center text-text-80 hover:bg-brand-150 transition-colors focus:outline-none"
    >
        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    </button>

    <main
        class="flex-1 h-screen overflow-y-auto [scrollbar-gutter:stable] transition-all duration-300 ease-in-out scroll-smooth"
        :class="($store.layout.isPinned || $store.layout.isHovered) ? 'ml-72 px-6 lg:px-10' : 'ml-0 px-14 lg:px-20'"
    >
        {{ $slot }}
    </main>

</body>
</html>
