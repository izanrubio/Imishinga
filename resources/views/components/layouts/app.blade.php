<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'Imishinga') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-dvh overflow-x-hidden">
    <div id="app" class="min-h-dvh">
        <header class="sticky top-0 z-30 border-b border-border bg-surface">
            <nav aria-label="Main" class="mx-auto flex h-14 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6">
                <a href="{{ route('boards.index') }}" class="-ml-1 flex items-center gap-2 rounded-lg p-1 text-base font-semibold tracking-tight">
                    <span class="flex size-7 items-center justify-center rounded-lg bg-accent text-accent-fg">
                        <x-icon name="kanban" class="size-4" />
                    </span>
                    Imishinga
                </a>

                @auth
                    <div class="flex items-center gap-1">
                        <span class="max-w-28 truncate text-sm font-medium text-ink-muted sm:max-w-none">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" aria-label="Log out"
                                class="inline-flex size-10 items-center justify-center gap-2 rounded-lg text-sm font-medium text-ink-muted hover:bg-surface-muted hover:text-ink sm:w-auto sm:px-3">
                                <x-icon name="log-out" class="size-4" />
                                <span class="hidden sm:inline">Log out</span>
                            </button>
                        </form>
                    </div>
                @endauth
            </nav>
        </header>

        <main>
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
