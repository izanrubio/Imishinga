<div class="shrink-0">
    <button type="button" wire:click="openModal"
        class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg bg-white/20 px-3 text-sm font-medium text-white transition-colors hover:bg-white/30 focus-visible:outline-white">
        <x-icon name="tag" />
        Labels
    </button>

    @if ($showModal)
        <x-modal label="Labels" class="max-w-lg"
            :subtitle="$board->name.' · '.$labels->count().' '.Str::plural('label', $labels->count())"
            onClose="$wire.confirmingDeleteId ? null : $wire.closeModal()">
            <ul class="flex flex-col gap-2">
                @if ($creating)
                    @include('livewire.boards.partials.label-form')
                @endif

                @forelse ($labels as $label)
                    @if ($editing?->id === $label->id)
                        @include('livewire.boards.partials.label-form')
                    @else
                        <li wire:key="label-{{ $label->id }}" class="flex items-center gap-3 rounded-lg border border-border p-2 pl-3">
                            <span class="h-6 w-10 shrink-0 rounded-md {{ $label->colorClass() }}" aria-hidden="true"></span>
                            <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ $label->name }}</span>
                            <span class="text-xs text-ink-subtle">{{ $label->cards_count }} {{ Str::plural('card', $label->cards_count) }}</span>
                            <x-icon-button aria-label="Edit label {{ $label->name }}" class="shrink-0" wire:click="startEdit({{ $label->id }})">
                                <x-icon name="pencil" />
                            </x-icon-button>
                            <x-icon-button variant="danger" aria-label="Delete label {{ $label->name }}" class="shrink-0" wire:click="confirmDelete({{ $label->id }})">
                                <x-icon name="trash" />
                            </x-icon-button>
                        </li>
                    @endif
                @empty
                    @unless ($creating)
                        <li class="rounded-lg border border-dashed border-border-strong px-3 py-6 text-center text-sm text-ink-subtle">No labels yet</li>
                    @endunless
                @endforelse
            </ul>

            @if ($confirmingDeleteId && ($deleting = $labels->firstWhere('id', $confirmingDeleteId)))
                <x-confirm-dialog label="Delete label" onClose="$wire.cancelDelete()">
                    <h3 class="mt-3 text-lg font-semibold tracking-tight">Delete &ldquo;{{ $deleting->name }}&rdquo;?</h3>
                    <p class="mt-1 text-sm text-ink-muted">It will be removed from {{ $deleting->cards_count }} {{ Str::plural('card', $deleting->cards_count) }}.</p>
                    <div class="mt-5 flex flex-wrap justify-end gap-2">
                        <x-button variant="secondary" wire:click="cancelDelete">Cancel</x-button>
                        <x-button variant="danger" wire:click="delete({{ $deleting->id }})" target="delete" loading-text="Deleting…">
                            Delete label
                        </x-button>
                    </div>
                </x-confirm-dialog>
            @endif

            <x-slot:footer>
                <x-button variant="secondary" class="mr-auto" wire:click="startCreate">
                    <x-icon name="plus" />
                    New label
                </x-button>
                <x-button wire:click="closeModal">Done</x-button>
            </x-slot:footer>
        </x-modal>
    @endif
</div>
