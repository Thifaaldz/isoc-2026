<!DOCTYPE html>
<html class="scroll-smooth" lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>@yield('title', 'ISOC Indonesia Jakarta Chapter')</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: { DEFAULT: '#002D56', light: '#003d75', dark: '#001833' },
                        blue: { DEFAULT: '#0060AC', light: '#0097DC', dark: '#004883' },
                        teal: { DEFAULT: '#00B4A0', light: '#00D4BC' },
                        grey: { 50: '#F7F8F9', 100: '#F0F1F3', 200: '#E1E3E6', 300: '#C4C8CE', 400: '#8B919A', 500: '#6B7280', 600: '#4B5563', 700: '#374151', 800: '#1F2937', 900: '#111827' },
                        'on-tertiary-fixed-variant': '#544600',
                        background: '#f8f9fa',
                        'on-tertiary': '#ffffff',
                        secondary: '#0060ac',
                        'surface-container-lowest': '#ffffff',
                        tertiary: '#705d00',
                        'surface-dim': '#d9dadb',
                        'inverse-on-surface': '#f0f1f2',
                        surface: '#f8f9fa',
                        outline: '#73777f',
                        'error-container': '#ffdad6',
                        'secondary-container': '#68abff',
                        'on-secondary-fixed': '#001c39',
                        'on-secondary-container': '#003e73',
                        'secondary-fixed': '#d4e3ff',
                        'on-surface': '#191c1d',
                        'tertiary-fixed-dim': '#e9c400',
                        'on-tertiary-container': '#4c3f00',
                        'on-background': '#191c1d',
                        'surface-variant': '#e1e3e4',
                        'primary-container': '#002d56',
                        'on-surface-variant': '#43474e',
                        'surface-bright': '#f8f9fa',
                        'primary-fixed-dim': '#a7c8fa',
                        'inverse-primary': '#a7c8fa',
                        'on-secondary': '#ffffff',
                        'surface-container-low': '#f3f4f5',
                        'on-primary': '#ffffff',
                        'on-primary-fixed-variant': '#254872',
                        'on-primary-container': '#7596c4',
                        error: '#ba1a1a',
                        'on-error-container': '#93000a',
                        'surface-tint': '#3f608b',
                        'on-error': '#ffffff',
                        'secondary-fixed-dim': '#a4c9ff',
                        'on-secondary-fixed-variant': '#004883',
                        'inverse-surface': '#2e3132',
                        'surface-container': '#edeeef',
                        'surface-container-highest': '#e1e3e4',
                        primary: '#001833',
                        'on-tertiary-fixed': '#221b00',
                        'on-primary-fixed': '#001c39',
                        'primary-fixed': '#d4e3ff',
                        'tertiary-fixed': '#ffe16d',
                        'outline-variant': '#c3c6d0',
                        'surface-container-high': '#e7e8e9',
                        'tertiary-container': '#c9a900',
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        headline: ['Plus Jakarta Sans', 'Inter', 'system-ui', 'sans-serif'],
                        body: ['Inter', 'system-ui', 'sans-serif'],
                        label: ['Inter', 'system-ui', 'sans-serif'],
                    },
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            display: inline-block;
            vertical-align: middle;
            line-height: 1;
        }
        [x-cloak] { display: none !important; }
        .bento-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 2rem;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-white font-sans text-grey-800 antialiased">

    @include('partials.navbar')

    <main class="pt-[72px]">
        @yield('content')
    </main>

    @include('partials.footer')

    @stack('scripts')
</body>
</html>
