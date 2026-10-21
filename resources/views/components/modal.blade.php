@props(['label', 'onClose', 'subtitle' => null])

@php
    $defaultWidth = str_contains((string) $attributes->get('class'), 'max-w-') ? '' : 'max-w-md';
@endphp

<div class="fixed inset-0 z-50 bg-scrim" aria-hidden="true"></div>
<div class="fixed inset-0 z-50 flex items-center justify-center p-4 max-sm:p-0"
    x-data
    x-init="document.body.style.overflow = 'hidden'; return () => { document.body.style.overflow = ''; };"
    x-on:keydown.escape.window="{{ $onClose }}">
    <div role="dialog" aria-modal="true" aria-label="{{ $label }}"
        x-on:click.outside="{{ $onClose }}"
        {{ $attributes->merge(['class' => 'flex w-full '.$defaultWidth.' flex-col bg-surface text-ink shadow-xl max-h-dvh max-sm:min-h-dvh sm:max-h-[calc(100dvh-4rem)] sm:rounded-2xl sm:border sm:border-border']) }}>
        @isset($header)
            {{ $header }}
        @else
            <div class="flex items-start justify-between gap-3 px-5 pt-4">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold tracking-tight">{{ $label }}</h2>
                    @if ($subtitle)
                        <p class="mt-0.5 text-sm text-ink-muted">{{ $subtitle }}</p>
                    @endif
                </div>
                <x-icon-button aria-label="Close" x-on:click="{{ $onClose }}">
                    <x-icon name="x" />
                </x-icon-button>
            </div>

        @endisset

        <div class="flex min-h-0 flex-1 flex-col gap-5 overflow-y-auto px-5 py-4">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="mt-auto flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-border bg-surface px-5 py-3 max-sm:pb-[max(0.75rem,env(safe-area-inset-bottom))]">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
