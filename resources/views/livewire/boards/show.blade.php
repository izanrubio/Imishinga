<div style="background-color: {{ $board->background_color ?? '#f3f4f6' }}" class="min-h-[calc(100vh-3.5rem)]">
    <div class="px-6 pt-6 pb-2 flex items-start justify-between gap-4">
        <div>
            <a href="{{ route('boards.index') }}" class="text-white/80 hover:text-white text-sm">&larr; Boards</a>
            <h1 class="text-2xl font-semibold text-white mt-1">{{ $board->name }}</h1>
            @if ($board->description)
                <p class="text-white/80 text-sm mt-1">{{ $board->description }}</p>
            @endif
        </div>

        <livewire:boards.label-manager :board="$board" :key="'label-manager-'.$board->id" />
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
                                @if ($card->labels->isNotEmpty())
                                    <div class="flex flex-wrap gap-1 mb-1.5">
                                        @foreach ($card->labels as $label)
                                            <span class="h-2 w-8 rounded-full" style="background-color: {{ $label->color }}" title="{{ $label->name }}"></span>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="flex items-start gap-2">
                                    <button type="button" x-on:click.stop="$wire.toggleCompleted({{ $card->id }})"
                                        title="{{ $card->completed_at ? 'Mark not complete' : 'Mark complete' }}"
                                        class="mt-0.5 flex-shrink-0 w-4 h-4 rounded-full border-2 {{ $card->completed_at ? 'bg-green-500 border-green-500' : 'border-gray-300 hover:border-gray-400' }}">
                                    </button>

                                    <div class="flex-1 min-w-0">
                                        <p class="{{ $card->completed_at ? 'text-gray-400 line-through' : 'text-gray-800' }}">{{ $card->title }}</p>
                                        @if ($card->description)
                                            <p class="text-gray-500 text-xs mt-1 truncate">{{ $card->description }}</p>
                                        @endif

                                        <div class="flex items-center gap-1.5 mt-1">
                                            @if ($card->due_date)
                                                @php $tone = $card->dueBadgeTone(); @endphp
                                                <span @class([
                                                    'inline-block text-xs px-1.5 py-0.5 rounded',
                                                    'bg-red-100 text-red-700' => $tone === 'overdue',
                                                    'bg-amber-100 text-amber-700' => $tone === 'today',
                                                    'bg-green-100 text-green-700' => $tone === 'completed',
                                                    'bg-gray-100 text-gray-600' => $tone === 'upcoming',
                                                ])>{{ $card->due_date->format('j M') }}</span>
                                            @endif

                                            @if ($card->checklist_items_count > 0)
                                                <span @class([
                                                    'inline-flex items-center gap-1 text-xs px-1.5 py-0.5 rounded',
                                                    'bg-green-100 text-green-700' => $card->checklist_items_done_count === $card->checklist_items_count,
                                                    'bg-gray-100 text-gray-600' => $card->checklist_items_done_count !== $card->checklist_items_count,
                                                ])>
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3">
                                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                                    </svg>
                                                    {{ $card->checklist_items_done_count }}/{{ $card->checklist_items_count }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
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
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 max-h-[90vh] overflow-y-auto" x-on:click.outside="$wire.closeCardModal()">
                <h2 class="text-lg font-semibold mb-4">Edit card</h2>

                <form id="save-card-form" wire:submit="saveCard" class="space-y-4">
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

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Labels</label>
                        <div class="flex flex-wrap gap-2">
                            @forelse ($labels as $label)
                                @php $assigned = $editingCard?->labels->contains($label->id); @endphp
                                <button type="button" wire:click="toggleCardLabel({{ $editingCard?->id }}, {{ $label->id }})"
                                    class="text-xs px-2 py-1 rounded-full border {{ $assigned ? 'text-white border-transparent' : 'text-gray-600 border-gray-300 hover:border-gray-400' }}"
                                    @style([ 'background-color: '.$label->color => $assigned ])>
                                    {{ $label->name }}
                                </button>
                            @empty
                                <p class="text-xs text-gray-400">No labels yet — create some from the board's "Labels" button.</p>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Due date</label>
                        <div class="flex gap-2 mt-1">
                            <input wire:model="cardDueDate" type="datetime-local"
                                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <button type="button" wire:click="clearDueDate"
                                class="px-3 py-1 text-sm rounded-md text-gray-600 hover:bg-gray-100">
                                Clear
                            </button>
                        </div>
                        @error('cardDueDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-2">
                        <input id="card-completed-toggle" type="checkbox" wire:click="toggleCompleted({{ $editingCard?->id }})"
                            @checked($editingCard?->completed_at)
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label for="card-completed-toggle" class="text-sm text-gray-700">Completed</label>
                    </div>

                </form>

                @if ($editingCard)
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Checklists</label>
                        <livewire:boards.checklist-manager :card="$editingCard" :key="'checklist-manager-'.$editingCard->id" />
                    </div>
                @endif

                <div class="flex justify-between items-center pt-4">
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
                        <button type="submit" form="save-card-form" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
