@props(['label', 'tone' => 'danger', 'onClose' => null])

@php
    $iconTone = $tone === 'warn' ? 'bg-warn-soft text-warn-ink' : 'bg-danger-soft text-danger-ink';
    $icon = $tone === 'warn' ? 'alert-circle' : 'trash';
@endphp

<div class="fixed inset-0 z-50 bg-scrim" aria-hidden="true"></div>
<div class="fixed inset-0 z-50 flex items-center justify-center p-4"
    x-data
    @if ($onClose) x-on:keydown.escape.window="{{ $onClose }}" @endif>
    <div role="alertdialog" aria-modal="true" aria-label="{{ $label }}"
        @if ($onClose) x-on:click.outside="{{ $onClose }}" @endif
        class="w-full max-w-sm rounded-2xl border border-border bg-surface p-5 shadow-xl">
        <div class="flex size-10 items-center justify-center rounded-full {{ $iconTone }}">
            <x-icon :name="$icon" class="size-5" />
        </div>

        {{ $slot }}
    </div>
</div>
