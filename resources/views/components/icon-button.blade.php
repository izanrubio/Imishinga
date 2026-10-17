@props(['variant' => 'neutral'])

@php
    $base = 'inline-flex size-10 items-center justify-center rounded-lg transition-colors';

    $variants = [
        'neutral' => 'text-ink-muted hover:bg-surface-muted hover:text-ink',
        'danger' => 'text-ink-muted hover:bg-danger-soft hover:text-danger-ink',
    ];

    $classes = $base.' '.($variants[$variant] ?? $variants['neutral']);
@endphp

<button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>
    {{ $slot }}
</button>
