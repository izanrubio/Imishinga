@props(['variant' => 'primary', 'target' => null, 'loadingText' => 'Saving…'])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg text-sm font-medium h-10 px-4 whitespace-nowrap transition-colors disabled:cursor-not-allowed';

    $variants = [
        'primary' => 'bg-accent text-accent-fg hover:bg-accent-hover disabled:bg-border-strong disabled:text-surface',
        'secondary' => 'border border-border-strong bg-surface text-ink hover:bg-surface-muted disabled:border-border disabled:text-ink-subtle disabled:hover:bg-surface',
        'ghost' => 'text-ink-muted hover:bg-surface-muted hover:text-ink disabled:text-ink-subtle disabled:hover:bg-transparent',
        'danger' => 'bg-danger text-accent-fg hover:bg-danger-hover disabled:bg-border-strong disabled:text-surface',
    ];

    $classes = $base.' '.($variants[$variant] ?? $variants['primary']);
@endphp

<button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}
    @if ($target) wire:loading.attr="disabled" wire:target="{{ $target }}" @endif>
    @if ($target)
        <span wire:loading.remove wire:target="{{ $target }}" class="inline-flex items-center gap-2">{{ $slot }}</span>
        <span wire:loading wire:target="{{ $target }}" class="inline-flex items-center gap-2">
            <x-icon name="loader" class="size-4 animate-spin motion-reduce:animate-none" />
            {{ $loadingText }}
        </span>
    @else
        {{ $slot }}
    @endif
</button>
