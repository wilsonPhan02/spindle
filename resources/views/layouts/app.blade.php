<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spindle - Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-bg-main antialiased min-h-screen flex overflow-hidden" x-data="{ isPinned: true, isHovered: false }">
    
    <x-sidebar />

    <div 
        x-show="!isPinned" 
        @mouseenter="isHovered = true" 
        class="fixed inset-y-0 left-0 w-3 z-40 bg-transparent cursor-pointer"
    ></div>

    <button 
        x-show="!isPinned && !isHovered" 
        @click="isPinned = true"
        x-transition.opacity
        class="fixed left-0 top-1/2 -translate-y-1/2 w-6 h-16 bg-brand-100 border border-subtext-70 border-l-0 rounded-r-full shadow-md z-50 flex items-center justify-center text-text-80 hover:bg-brand-150 transition-colors focus:outline-none"
    >
        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    </button>

    <main 
        class="flex-1 h-screen overflow-y-auto transition-all duration-300 ease-in-out"
        :class="(isPinned || isHovered) ? 'ml-72' : 'ml-0'"
    >
        {{ $slot }}
    </main>

</body>
</html>