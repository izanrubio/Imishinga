<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-10">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Boards</h1>
            <p class="mt-1 text-sm text-ink-muted">{{ $boards->count() }} {{ Str::plural('board', $boards->count()) }}</p>
        </div>
        <x-button wire:click="createNew">
            <x-icon name="plus" />
            Create board
        </x-button>
    </div>

    @if ($boards->isEmpty())
        <div class="mt-6 rounded-xl border border-dashed border-border-strong bg-surface px-6 py-14 text-center">
            <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-accent-soft text-accent-ink">
                <x-icon name="kanban" class="size-6" />
            </span>
            <h3 class="mt-4 text-lg font-semibold tracking-tight">No boards yet</h3>
            <p class="mx-auto mt-1 max-w-sm text-sm text-ink-muted">Create a board to start organizing work into lists and cards.</p>
            <div class="mt-5 flex justify-center">
                <x-button wire:click="createNew">
                    <x-icon name="plus" />
                    Create board
                </x-button>
            </div>
        </div>
    @else
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($boards as $board)
                <article wire:key="board-{{ $board->id }}"
                    class="relative flex flex-col overflow-hidden rounded-xl border border-border bg-surface shadow-xs transition-shadow hover:shadow-md has-[a:focus-visible]:ring-2 has-[a:focus-visible]:ring-accent">
                    <div class="flex h-20 items-start gap-1.5 {{ $board->colorClass() }} p-3" aria-hidden="true">
                        <span class="h-9 w-10 rounded bg-white/25"></span>
                        <span class="h-12 w-10 rounded bg-white/25"></span>
                        <span class="h-6 w-10 rounded bg-white/25"></span>
                    </div>

                    <div class="flex flex-1 flex-col gap-1 px-4 pb-2 pt-3">
                        <h2 class="text-base font-semibold tracking-tight">
                            <a href="{{ route('boards.show', $board) }}" class="outline-none after:absolute after:inset-0">{{ $board->name }}</a>
                        </h2>
                        <p class="line-clamp-2 min-h-10 text-sm leading-5 text-ink-muted">{{ $board->description }}</p>
                    </div>

                    <div class="relative z-10 flex items-center justify-end gap-1 px-2 pb-2">
                        <x-icon-button aria-label="Edit board {{ $board->name }}" wire:click="editBoard({{ $board->id }})">
                            <x-icon name="pencil" />
                        </x-icon-button>
                        <x-icon-button variant="danger" aria-label="Delete board {{ $board->name }}"
                            wire:click="confirmDelete({{ $board->id }}, {{ $board->lists_count }}, {{ $board->cards_count }})">
                            <x-icon name="trash" />
                        </x-icon-button>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    @if ($showFormModal)
        <x-modal :label="$editing ? 'Edit board' : 'Create board'" onClose="$wire.closeFormModal()">
            <form id="board-form" wire:submit="save" class="contents">
                <x-input label="Name" name="name" wire:model="name" placeholder="e.g. Web freelance" :error="$errors->first('name')" />

                <x-textarea label="Description" name="description" wire:model="description" rows="3" placeholder="Optional" :error="$errors->first('description')" />

                <div class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">Colour</span>
                    <div role="radiogroup" aria-label="Board colour" class="flex flex-wrap gap-2">
                        @foreach (\App\Enums\BoardColor::cases() as $color)
                            @php $checked = $background_color === $color->value; @endphp
                            <button type="button" role="radio" aria-checked="{{ $checked ? 'true' : 'false' }}" aria-label="{{ $color->value }}"
                                wire:click="$set('background_color', '{{ $color->value }}')"
                                @class([
                                    'flex size-10 items-center justify-center rounded-lg text-accent-fg',
                                    $color->bgClass(),
                                    'ring-2 ring-ink ring-offset-2' => $checked,
                                    'hover:ring-2 hover:ring-border-strong hover:ring-offset-2' => ! $checked,
                                ])>
                                @if ($checked)
                                    <x-icon name="check" />
                                @endif
                            </button>
                        @endforeach
                    </div>
                    @error('background_color') <p class="flex items-center gap-1.5 text-sm text-danger-ink"><x-icon name="alert-circle" class="size-4" />{{ $message }}</p> @enderror
                </div>
            </form>

            <x-slot:footer>
                <x-button variant="ghost" wire:click="closeFormModal">Cancel</x-button>
                <x-button type="submit" form="board-form" target="save" :loading-text="$editing ? 'Saving…' : 'Creating…'">
                    {{ $editing ? 'Save changes' : 'Create board' }}
                </x-button>
            </x-slot:footer>
        </x-modal>
    @endif

    @if ($deleting)
        <x-confirm-dialog label="Delete board" onClose="$wire.cancelDelete()">
            <h3 class="mt-3 text-lg font-semibold tracking-tight">Delete &ldquo;{{ $deleting->name }}&rdquo;?</h3>
            <p class="mt-1 text-sm text-ink-muted">
                This board, its {{ $deletingListsCount }} {{ Str::plural('list', $deletingListsCount) }}
                and {{ $deletingCardsCount }} {{ Str::plural('card', $deletingCardsCount) }} will be permanently deleted.
            </p>
            <div class="mt-5 flex flex-wrap justify-end gap-2">
                <x-button variant="secondary" wire:click="cancelDelete">Cancel</x-button>
                <x-button variant="danger" wire:click="deleteBoard({{ $deleting->id }})" target="deleteBoard" loading-text="Deleting…">
                    Delete board
                </x-button>
            </div>
        </x-confirm-dialog>
    @endif
</div>
