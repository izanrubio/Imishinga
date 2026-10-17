<div class="{{ $board->colorClass() }} min-h-[calc(100vh-3.5rem)]">
    <div class="px-4 sm:px-6 pt-6 pb-2 flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <a href="{{ route('boards.index') }}" class="text-white/80 hover:text-white text-sm">&larr; Boards</a>
            <h1 class="text-2xl font-semibold text-white mt-1 break-words">{{ $board->name }}</h1>
            @if ($board->description)
                <p class="text-white/80 text-sm mt-1 break-words">{{ $board->description }}</p>
            @endif
        </div>

        <livewire:boards.label-manager :board="$board" :key="'label-manager-'.$board->id" />
    </div>

    <div class="flex gap-4 overflow-x-auto snap-x snap-mandatory items-start px-4 sm:px-6 pb-6">
        <div wire:ignore style="display: contents"
            x-data
            x-init="
                Sortable.create($el.nextElementSibling, {
                    animation: 150,
                    handle: '.list-drag-handle',
                    draggable: '[data-list-id]',
                    delay: 180,
                    delayOnTouchOnly: true,
                    touchStartThreshold: 5,
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
                    class="w-[85vw] max-w-[320px] sm:w-72 sm:max-w-none flex-shrink-0 snap-start bg-gray-100 rounded-lg p-3">
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
                            class="text-gray-400 hover:text-red-600 text-sm w-10 h-10 -m-3 inline-flex items-center justify-center flex-shrink-0">&times;</button>
                    </div>

                    <div wire:ignore style="display: contents"
                        x-data
                        x-init="
                            Sortable.create($el.nextElementSibling, {
                                group: 'cards',
                                animation: 150,
                                draggable: '[data-card-id]',
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
                                        class="mt-0.5 flex-shrink-0 w-10 h-10 -m-3 inline-flex items-center justify-center">
                                        <span class="w-4 h-4 rounded-full border-2 {{ $card->completed_at ? 'bg-green-500 border-green-500' : 'border-gray-300 hover:border-gray-400' }}"></span>
                                    </button>

                                    <div class="flex-1 min-w-0">
                                        <p class="{{ $card->completed_at ? 'text-gray-400 line-through' : 'text-gray-800' }}">{{ $card->title }}</p>
                                        @if ($card->description)
                                            <p class="text-gray-500 text-xs mt-1 truncate">{{ $card->description }}</p>
                                        @endif

                                        <div class="flex items-center gap-1.5 mt-1 flex-wrap">
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

                                            @if ($card->comments_count > 0)
                                                <span class="inline-flex items-center gap-1 text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3">
                                                        <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a9.06 9.06 0 01-2.347-.306c-.584.296-1.925.864-4.181 1.234-.2.032-.352-.176-.273-.362.354-.836.674-1.95.77-2.966C2.249 13.391 2 11.686 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7z" clip-rule="evenodd" />
                                                    </svg>
                                                    {{ $card->comments_count }}
                                                </span>
                                            @endif

                                            @if ($card->attachments_count > 0)
                                                <span class="inline-flex items-center gap-1 text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3">
                                                        <path fill-rule="evenodd" d="M15.621 4.379a3 3 0 00-4.242 0l-7 7a3 3 0 004.241 4.243h.001l.497-.5a.75.75 0 011.064 1.057l-.498.501-.002.002a4.5 4.5 0 01-6.364-6.364l7-7a4.5 4.5 0 016.368 6.36l-3.455 3.553A2.625 2.625 0 119.52 9.52l3.45-3.451a.75.75 0 111.061 1.06l-3.45 3.451a1.125 1.125 0 001.587 1.595l3.454-3.553a3 3 0 000-4.242z" clip-rule="evenodd" />
                                                    </svg>
                                                    {{ $card->attachments_count }}
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

        <form wire:submit="addList" class="w-[85vw] max-w-[320px] sm:w-72 sm:max-w-none flex-shrink-0 snap-start">
            <input wire:model="newListName" type="text" placeholder="+ Add a list"
                class="w-full rounded-lg bg-white/90 border-transparent text-sm px-3 py-2 placeholder-gray-500 focus:bg-white focus:border-blue-500 focus:ring-blue-500">
            @error('newListName') <p class="mt-1 text-xs text-red-100 bg-red-600/80 rounded px-2 py-1">{{ $message }}</p> @enderror
        </form>
    </div>

    @if ($showCardModal)
        @include('livewire.boards.partials.card-modal')
    @endif
</div>
