<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title }}</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script>
            (() => {
                try {
                    const stored = localStorage.getItem('theme');
                    const isDark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
                    if (isDark) document.documentElement.classList.add('dark');
                    else document.documentElement.classList.remove('dark');
                } catch (e) {}
            })();
        </script>
    </head>
    <body class="font-sans antialiased text-text-80 bg-brand-50 overflow-x-hidden transition-colors duration-200">
        <x-desktop-only />

        {{ $slot }}
    </body>
</html>