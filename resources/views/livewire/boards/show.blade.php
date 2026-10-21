@php
    $dueTones = ['overdue' => 'danger', 'today' => 'warn', 'completed' => 'ok', 'upcoming' => 'neutral'];
    $deletingList = $confirmingDeleteListId ? $lists->firstWhere('id', $confirmingDeleteListId) : null;
@endphp

<div class="flex min-h-0 flex-1 flex-col">
    <div class="flex shrink-0 items-start justify-between gap-3 bg-scrim px-4 py-3 text-white sm:px-6">
        <div class="min-w-0">
            <h1 class="truncate text-lg font-semibold tracking-tight">{{ $board->name }}</h1>
            @if ($board->description)
                <p class="truncate text-sm text-white/90">{{ $board->description }}</p>
            @endif
        </div>

        <livewire:boards.label-manager :board="$board" :key="'label-manager-'.$board->id" />
    </div>

    <div class="lists-row flex min-h-0 flex-1 snap-x snap-mandatory items-start gap-3 overflow-x-auto scroll-px-4 px-4 py-4 sm:snap-none sm:px-6">
        <div wire:ignore style="display: contents"
            x-data
            x-init="
                Sortable.create($el.nextElementSibling, {
                    animation: 150,
                    handle: '.list-drag-handle',
                    draggable: '[data-list-column]',
                    ghostClass: 'list-ghost',
                    chosenClass: 'list-chosen',
                    dragClass: 'list-drag',
                    delay: 180,
                    delayOnTouchOnly: true,
                    touchStartThreshold: 5,
                    onEnd: () => {
                        $wire.reorderLists(
                            Array.from($el.nextElementSibling.querySelectorAll('[data-list-column]'))
                                .map(el => el.dataset.listColumn)
                        )
                    },
                })
            "></div>

        <div data-lists-container class="flex items-start gap-3 self-stretch">
            @foreach ($lists as $list)
                <section wire:key="list-{{ $list->id }}" data-list-column="{{ $list->id }}" aria-label="{{ $list->name }}"
                    class="list-column flex max-h-full w-[85vw] shrink-0 snap-center flex-col rounded-xl bg-list shadow-sm sm:w-72">
                    <div x-data="{ editing: false, name: @js($list->name) }" class="flex items-center gap-0.5 px-1.5 pt-2">
                        <button type="button" aria-label="Drag list {{ $list->name }}"
                            class="list-drag-handle flex size-10 shrink-0 cursor-grab items-center justify-center rounded-md text-ink-subtle hover:bg-surface-muted active:cursor-grabbing sm:size-8">
                            <x-icon name="grip" />
                        </button>

                        <template x-if="!editing">
                            <button type="button" x-on:click="editing = true" x-text="name"
                                class="min-w-0 flex-1 truncate rounded-md px-2 py-1.5 text-left text-sm font-semibold hover:bg-surface-muted"></button>
                        </template>
                        <template x-if="editing">
                            <input type="text" aria-label="List name" x-model="name" x-ref="input"
                                x-on:blur="editing = false; $wire.renameList({{ $list->id }}, name)"
                                x-on:keydown.enter="editing = false; $wire.renameList({{ $list->id }}, name)"
                                x-on:keydown.escape="editing = false; name = @js($list->name)"
                                x-init="$watch('editing', value => value && $nextTick(() => $refs.input.focus()))"
                                class="min-w-0 flex-1 rounded-md border border-accent bg-surface px-2 py-1 text-sm font-semibold outline-none ring-2 ring-accent-soft">
                        </template>

                        <span class="px-1 text-xs font-medium text-ink-subtle">{{ $list->cards_count }}</span>

                        <button type="button" aria-label="Delete list {{ $list->name }}" wire:click="confirmDeleteList({{ $list->id }})"
                            class="flex size-10 shrink-0 items-center justify-center rounded-md text-ink-subtle hover:bg-danger-soft hover:text-danger-ink sm:size-8">
                            <x-icon name="trash" />
                        </button>
                    </div>

                    <div wire:ignore style="display: contents"
                        x-data
                        x-init="
                            Sortable.create($el.nextElementSibling, {
                                group: 'cards',
                                animation: 150,
                                draggable: '[data-card-id]',
                                ghostClass: 'card-ghost',
                                chosenClass: 'card-chosen',
                                dragClass: 'card-drag',
                                delay: 180,
                                delayOnTouchOnly: true,
                                touchStartThreshold: 5,
                                onEnd: (evt) => {
                                    $wire.reorderCards(
                                        Number(evt.item.dataset.cardId),
                                        Number(evt.to.dataset.listId),
                                        Array.from(evt.to.querySelectorAll('[data-card-id]')).map(el => el.dataset.cardId)
                                    )
                                },
                            })
                        "></div>

                    <div data-cards-container data-list-id="{{ $list->id }}" class="flex min-h-10 flex-col gap-2 overflow-y-auto px-2 py-2">
                        @forelse ($list->cards as $card)
                            @php
                                $tone = $card->dueBadgeTone();
                                $checklistComplete = $card->checklist_items_done_count === $card->checklist_items_count;
                            @endphp
                            <article wire:key="card-{{ $card->id }}" data-card-id="{{ $card->id }}"
                                x-on:click="$wire.editCard({{ $card->id }})"
                                class="cursor-pointer rounded-lg border border-border bg-surface p-2.5 shadow-xs transition-shadow hover:border-border-strong hover:shadow-sm">
                                @if ($card->labels->isNotEmpty())
                                    <div class="mb-1.5 flex flex-wrap gap-1">
                                        @foreach ($card->labels as $label)
                                            <span class="h-1.5 w-9 rounded-full {{ $label->colorClass() }}" title="{{ $label->name }}"></span>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="flex items-start gap-2">
                                    <button type="button" role="checkbox" aria-checked="{{ $card->completed_at ? 'true' : 'false' }}"
                                        aria-label="{{ $card->completed_at ? 'Mark incomplete' : 'Mark complete' }}"
                                        x-on:click.stop="$wire.toggleCompleted({{ $card->id }})"
                                        @class([
                                            'relative mt-px flex size-5 shrink-0 items-center justify-center rounded-full after:absolute',
                                            'bg-ok text-accent-fg after:-inset-2.5' => $card->completed_at,
                                            'border-2 border-border-strong text-transparent transition-colors after:-inset-3 hover:border-ok hover:text-ok' => ! $card->completed_at,
                                        ])>
                                        <x-icon name="check" class="size-3" />
                                    </button>

                                    <div class="min-w-0 flex-1">
                                        <h3 @class([
                                            'text-sm font-medium leading-snug',
                                            'text-ink-subtle line-through' => $card->completed_at,
                                            'text-ink' => ! $card->completed_at,
                                        ])>{{ $card->title }}</h3>

                                        @if ($card->description)
                                            <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-ink-muted">{{ $card->description }}</p>
                                        @endif

                                        @if ($card->due_date || $card->checklist_items_count > 0 || $card->comments_count > 0 || $card->attachments_count > 0)
                                            <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1">
                                                @if ($card->due_date)
                                                    <x-card-badge :icon="$tone === 'completed' ? 'check' : 'calendar'" :tone="$dueTones[$tone]">{{ $card->due_date->format('j M') }}</x-card-badge>
                                                @endif

                                                @if ($card->checklist_items_count > 0)
                                                    <x-card-badge icon="checklist" :tone="$checklistComplete ? 'ok' : 'neutral'">{{ $card->checklist_items_done_count }}/{{ $card->checklist_items_count }}</x-card-badge>
                                                @endif

                                                @if ($card->comments_count > 0)
                                                    <x-card-badge icon="comment">{{ $card->comments_count }}</x-card-badge>
                                                @endif

                                                @if ($card->attachments_count > 0)
                                                    <x-card-badge icon="paperclip">{{ $card->attachments_count }}</x-card-badge>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-lg border border-dashed border-border-strong px-3 py-6 text-center text-sm text-ink-subtle">No cards yet</div>
                        @endforelse
                    </div>

                    <div x-data="{ open: false }">
                        <button type="button" x-show="!open" x-cloak
                            x-on:click="open = true; $nextTick(() => $refs.title.focus())"
                            class="mx-2 mb-2 flex h-10 items-center gap-2 rounded-lg px-2 text-sm text-ink-muted transition-colors hover:bg-surface-muted hover:text-ink">
                            <x-icon name="plus" />
                            Add a card
                        </button>

                        <form x-show="open" x-cloak wire:submit="addCard({{ $list->id }})" class="px-2 pb-2">
                            <label class="sr-only" for="add-card-{{ $list->id }}">Card title</label>
                            <input id="add-card-{{ $list->id }}" x-ref="title" wire:model="newCardTitle.{{ $list->id }}" type="text" autocomplete="off"
                                x-on:keydown.escape="open = false"
                                class="h-10 w-full rounded-lg border border-accent bg-surface px-3 text-sm outline-none ring-2 ring-accent-soft">
                            <p wire:loading.remove wire:target="addCard({{ $list->id }})" class="mt-1.5 px-1 text-xs text-ink-subtle">Enter to add · Esc to close</p>
                            <p wire:loading wire:target="addCard({{ $list->id }})" class="mt-1.5 flex items-center gap-1.5 px-1 text-xs text-ink-subtle">
                                <x-icon name="loader" class="size-3 animate-spin motion-reduce:animate-none" />
                                Adding…
                            </p>
                        </form>
                    </div>
                </section>
            @endforeach
        </div>

        <div x-data="{ open: false }" class="w-[85vw] shrink-0 snap-center sm:w-72">
            <button type="button" x-show="!open" x-cloak
                x-on:click="open = true; $nextTick(() => $refs.listName.focus())"
                class="flex h-11 w-full items-center gap-2 rounded-xl bg-white/20 px-4 text-sm font-medium text-white transition-colors hover:bg-white/30 focus-visible:outline-white">
                <x-icon name="plus" />
                Add list
            </button>

            <form x-show="open" x-cloak wire:submit="addList" class="rounded-xl bg-list p-2 shadow-sm">
                <label class="sr-only" for="add-list-name">List name</label>
                <input id="add-list-name" x-ref="listName" wire:model="newListName" type="text" autocomplete="off"
                    x-on:keydown.escape="open = false"
                    @error('newListName') aria-invalid="true" @enderror
                    @class([
                        'h-10 w-full rounded-lg border bg-surface px-3 text-sm outline-none ring-2',
                        'border-accent ring-accent-soft' => ! $errors->has('newListName'),
                        'border-danger ring-danger-soft' => $errors->has('newListName'),
                    ])>
                @error('newListName')
                    <p class="mt-1.5 flex items-center gap-1.5 px-1 text-sm text-danger-ink"><x-icon name="alert-circle" class="size-4" />{{ $message }}</p>
                @else
                    <p wire:loading.remove wire:target="addList" class="mt-1.5 px-1 text-xs text-ink-subtle">Enter to add · Esc to close</p>
                @enderror
                <p wire:loading wire:target="addList" class="mt-1.5 flex items-center gap-1.5 px-1 text-xs text-ink-subtle">
                    <x-icon name="loader" class="size-3 animate-spin motion-reduce:animate-none" />
                    Adding…
                </p>
            </form>
        </div>

        <div class="w-1 shrink-0 sm:hidden" aria-hidden="true"></div>
    </div>

    @if ($deletingList)
        <x-confirm-dialog label="Delete list" onClose="$wire.cancelDeleteList()">
            <h3 class="mt-3 text-lg font-semibold tracking-tight">Delete &ldquo;{{ $deletingList->name }}&rdquo;?</h3>
            <p class="mt-1 text-sm text-ink-muted">
                This list and its {{ $deletingList->cards_count }} {{ Str::plural('card', $deletingList->cards_count) }} will be permanently deleted.
            </p>
            <div class="mt-5 flex flex-wrap justify-end gap-2">
                <x-button variant="secondary" wire:click="cancelDeleteList">Cancel</x-button>
                <x-button variant="danger" wire:click="deleteList({{ $deletingList->id }})" target="deleteList" loading-text="Deleting…">
                    Delete list
                </x-button>
            </div>
        </x-confirm-dialog>
    @endif

    @if ($showCardModal)
        @include('livewire.boards.partials.card-modal')
    @endif
</div>
