<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'sena')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">
    <header class="bg-white border-b">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="{{ url('/webinars') }}" class="font-bold text-lg text-indigo-600">sena</a>
            <nav class="flex items-center gap-3 text-sm">
                <a href="{{ url('/webinars') }}" class="hover:text-indigo-600">Daftar Webinar</a>
                <a href="{{ url('/login') }}" class="hover:text-indigo-600">Login</a>
                <a href="{{ url('/student/register') }}" class="px-4 py-1.5 rounded-lg bg-indigo-600 text-white font-medium">Daftar Student</a>
            </nav>
        </div>
    </header>

    <main class="flex-1">
        @yield('content')
    </main>

    <footer class="bg-white border-t py-6 text-center text-xs text-gray-400">
        &copy; {{ date('Y') }} sena. All rights reserved.
    </footer>

    @livewireScripts
</body>
</html>
