<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'Trello') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 text-gray-900 antialiased overflow-x-hidden">
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-14 gap-2">
            <a href="{{ route('boards.index') }}" class="font-semibold text-gray-800 flex-shrink-0">Trello Clone</a>

            @auth
                <div class="flex items-center gap-2 sm:gap-4 text-sm min-w-0">
                    <span class="text-gray-600 truncate max-w-[8rem] sm:max-w-none">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="flex-shrink-0">
                        @csrf
                        <button type="submit" class="text-gray-600 hover:text-gray-900 inline-flex items-center min-h-[40px] px-1">Log out</button>
                    </form>
                </div>
            @endauth
        </div>
    </nav>

    <main>
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
