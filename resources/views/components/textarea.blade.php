@props(['label' => null, 'error' => null, 'help' => null, 'id' => null])

@php
    $id = $id ?? $attributes->get('name') ?? 'field-'.Str::random(6);
    $describedBy = $id.'-msg';

    $base = 'w-full rounded-lg border bg-surface px-3 py-2 text-sm placeholder:text-ink-subtle';

    $state = match (true) {
        (bool) $error => 'border-danger ring-2 ring-danger-soft',
        $attributes->has('disabled') => 'border-border bg-surface-muted text-ink-subtle',
        default => 'border-border-strong',
    };
@endphp

<div class="flex flex-col gap-1.5">
    @if ($label)
        <label for="{{ $id }}" class="text-sm font-medium">{{ $label }}</label>
    @endif

    <textarea id="{{ $id }}" {{ $attributes->merge(['class' => $base.' '.$state]) }}
        @if ($error) aria-invalid="true" aria-describedby="{{ $describedBy }}" @endif>{{ $slot }}</textarea>

    @if ($error)
        <p id="{{ $describedBy }}" class="flex items-center gap-1.5 text-sm text-danger-ink">
            <x-icon name="alert-circle" class="size-4" />
            {{ $error }}
        </p>
    @elseif ($help)
        <p class="text-sm text-ink-subtle">{{ $help }}</p>
    @endif
</div>
