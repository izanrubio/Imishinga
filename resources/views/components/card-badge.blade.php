@props(['icon', 'tone' => 'plain'])

@php
    $tones = [
        'plain' => 'text-ink-muted',
        'neutral' => 'rounded-md bg-surface-muted px-1.5 text-ink-muted',
        'danger' => 'rounded-md bg-danger-soft px-1.5 text-danger-ink',
        'warn' => 'rounded-md bg-warn-soft px-1.5 text-warn-ink',
        'ok' => 'rounded-md bg-ok-soft px-1.5 text-ok-ink',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex h-5 items-center gap-1 text-xs font-medium '.($tones[$tone] ?? $tones['plain'])]) }}>
    <x-icon :name="$icon" class="size-3" />
    {{ $slot }}
</span>
