<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page not found · {{ config('app.name', 'Imishinga') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh overflow-x-hidden bg-canvas">
    <div class="flex min-h-dvh flex-col items-center justify-center px-6 py-14 text-center">
        <p class="text-sm font-semibold text-accent-ink">404</p>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight">Page not found</h1>
        <p class="mt-2 max-w-sm text-sm text-ink-muted">The page you are looking for does not exist or you do not have access to it.</p>
        <div class="mt-6">
            <a href="{{ route('boards.index') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg text-sm font-medium h-10 px-4 whitespace-nowrap transition-colors bg-accent text-accent-fg hover:bg-accent-hover">
                <x-icon name="arrow-left" />
                Back to boards
            </a>
        </div>
    </div>
</body>
</html>
