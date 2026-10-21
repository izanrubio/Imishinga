<li wire:key="label-form-{{ $editing?->id ?? 'new' }}"
    class="flex flex-col gap-4 rounded-lg border border-accent bg-accent-soft/40 p-3 ring-2 ring-accent-soft">
    <form wire:submit="save" class="contents">
        <x-input label="Label name" name="name" wire:model="name" x-init="$el.focus()" :error="$errors->first('name')" />

        <div class="flex flex-col gap-1.5">
            <span class="text-sm font-medium">Colour</span>
            <div role="radiogroup" aria-label="Label colour" class="flex flex-wrap gap-2">
                @foreach (\App\Enums\LabelColor::cases() as $option)
                    @php $checked = $color === $option->value; @endphp
                    <button type="button" role="radio" aria-checked="{{ $checked ? 'true' : 'false' }}" aria-label="{{ $option->value }}"
                        wire:click="$set('color', '{{ $option->value }}')"
                        @class([
                            'flex size-10 items-center justify-center rounded-lg text-accent-fg',
                            $option->bgClass(),
                            'ring-2 ring-ink ring-offset-2' => $checked,
                            'hover:ring-2 hover:ring-border-strong hover:ring-offset-2' => ! $checked,
                        ])>
                        @if ($checked)
                            <x-icon name="check" />
                        @endif
                    </button>
                @endforeach
            </div>
            @error('color') <p class="flex items-center gap-1.5 text-sm text-danger-ink"><x-icon name="alert-circle" class="size-4" />{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end gap-2">
            <x-button variant="ghost" wire:click="cancelForm">Cancel</x-button>
            <x-button type="submit" target="save">Save label</x-button>
        </div>
    </form>
</li>
