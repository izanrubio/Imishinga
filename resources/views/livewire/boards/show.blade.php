<div style="background-color: {{ $board->background_color ?? '#f3f4f6' }}" class="min-h-[calc(100vh-3.5rem)]">
    <div class="px-6 pt-6 pb-2">
        <a href="{{ route('boards.index') }}" class="text-white/80 hover:text-white text-sm">&larr; Boards</a>
        <h1 class="text-2xl font-semibold text-white mt-1">{{ $board->name }}</h1>
        @if ($board->description)
            <p class="text-white/80 text-sm mt-1">{{ $board->description }}</p>
        @endif
    </div>

    <div class="flex gap-4 overflow-x-auto items-start px-6 pb-6">
        <div wire:ignore style="display: contents"
            x-data
            x-init="
                Sortable.create($el.nextElementSibling, {
                    animation: 150,
                    handle: '.list-drag-handle',
                    draggable: '[data-list-id]',
                    onEnd: () => {
                        $wire.reorderLists(
                            Array.from($el.nextElementSibling.querySelectorAll('[data-list-id]'))
                                .map(el => el.dataset.listId)
                        )
                    },
                })
            "></div>

        <div data-lists-container class="flex gap-4">
            @foreach ($lists as $list)
                <div wire:key="list-{{ $list->id }}" data-list-id="{{ $list->id }}"
                    class="w-72 flex-shrink-0 bg-gray-100 rounded-lg p-3">
                    <div x-data="{ editing: false, name: @js($list->name) }" class="flex items-start justify-between gap-2 mb-3 list-drag-handle cursor-grab active:cursor-grabbing">
                        <template x-if="!editing">
                            <h2 x-on:click="editing = true" class="font-medium text-gray-800 cursor-text px-1 py-0.5 rounded hover:bg-gray-200 flex-1" x-text="name"></h2>
                        </template>
                        <template x-if="editing">
                            <input type="text" x-model="name" x-ref="input"
                                x-on:blur="editing = false; $wire.renameList({{ $list->id }}, name)"
                                x-on:keydown.enter="editing = false; $wire.renameList({{ $list->id }}, name)"
                                x-on:keydown.escape="editing = false; name = @js($list->name)"
                                x-init="$watch('editing', value => value && $nextTick(() => $refs.input.focus()))"
                                class="flex-1 rounded border-gray-300 text-sm px-1 py-0.5">
                        </template>

                        <button type="button"
                            x-on:click="if (confirm('Delete this list and its cards?')) { $wire.deleteList({{ $list->id }}) }"
                            class="text-gray-400 hover:text-red-600 text-sm">&times;</button>
                    </div>

                    <div wire:ignore style="display: contents"
                        x-data
                        x-init="
                            Sortable.create($el.nextElementSibling, {
                                group: 'cards',
                                animation: 150,
                                draggable: '[data-card-id]',
                                onEnd: (evt) => {
                                    $wire.reorderCards(
                                        Number(evt.item.dataset.cardId),
                                        Number(evt.to.dataset.listId),
                                        Array.from(evt.to.querySelectorAll('[data-card-id]')).map(el => el.dataset.cardId)
                                    )
                                },
                            })
                        "></div>

                    <div data-cards-container data-list-id="{{ $list->id }}" class="space-y-2 min-h-[2.5rem]">
                        @forelse ($list->cards as $card)
                            <div wire:key="card-{{ $card->id }}" data-card-id="{{ $card->id }}"
                                x-on:click="$wire.editCard({{ $card->id }})"
                                class="bg-white rounded-md shadow-sm px-3 py-2 text-sm cursor-pointer hover:ring-2 hover:ring-blue-300">
                                <p class="text-gray-800">{{ $card->title }}</p>
                                @if ($card->description)
                                    <p class="text-gray-500 text-xs mt-1 truncate">{{ $card->description }}</p>
                                @endif
                            </div>
                        @empty
                            <div class="text-sm text-gray-400 italic px-1 py-6 text-center border-2 border-dashed border-gray-200 rounded">
                                No cards yet
                            </div>
                        @endforelse
                    </div>

                    <form wire:submit="addCard({{ $list->id }})" class="mt-2">
                        <input wire:model="newCardTitle.{{ $list->id }}" type="text" placeholder="+ Add a card"
                            class="w-full rounded-md bg-white border-gray-200 text-sm px-2 py-1.5 placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500">
                    </form>
                </div>
            @endforeach
        </div>

        <form wire:submit="addList" class="w-72 flex-shrink-0">
            <input wire:model="newListName" type="text" placeholder="+ Add a list"
                class="w-full rounded-lg bg-white/90 border-transparent text-sm px-3 py-2 placeholder-gray-500 focus:bg-white focus:border-blue-500 focus:ring-blue-500">
            @error('newListName') <p class="mt-1 text-xs text-red-100 bg-red-600/80 rounded px-2 py-1">{{ $message }}</p> @enderror
        </form>
    </div>

    @if ($showCardModal)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" x-data x-on:keydown.escape.window="$wire.closeCardModal()">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6" x-on:click.outside="$wire.closeCardModal()">
                <h2 class="text-lg font-semibold mb-4">Edit card</h2>

                <form wire:submit="saveCard" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Title</label>
                        <input wire:model="cardTitle" type="text" autofocus
                            class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('cardTitle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea wire:model="cardDescription" rows="4"
                            class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        @error('cardDescription') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-between items-center pt-2">
                        <button type="button"
                            x-on:click="if (confirm('Delete this card?')) { $wire.deleteCard({{ $editingCard?->id }}) }"
                            class="px-3 py-2 text-sm rounded-md text-red-600 hover:bg-red-50">
                            Delete
                        </button>

                        <div class="flex gap-2">
                            <button type="button" wire:click="closeCardModal"
                                class="px-4 py-2 text-sm rounded-md text-gray-700 hover:bg-gray-100">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">
                                Save
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
