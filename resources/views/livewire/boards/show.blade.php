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
                    draggable: '[data-list-id]',
                    onEnd: () => {
                        $wire.reorderLists(
                            Array.from($el.nextElementSibling.querySelectorAll('[data-list-id]'))
                                .map(el => el.dataset.listId)
                        )
                    },
                })
            ">
        </div>

        <div data-lists-container class="flex gap-4">
            @foreach ($lists as $list)
                <div wire:key="list-{{ $list->id }}" data-list-id="{{ $list->id }}"
                    class="w-72 flex-shrink-0 bg-gray-100 rounded-lg p-3 cursor-grab active:cursor-grabbing">
                    <div x-data="{ editing: false, name: @js($list->name) }" class="flex items-start justify-between gap-2 mb-3">
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

                    <div class="text-sm text-gray-400 italic px-1 py-6 text-center border-2 border-dashed border-gray-200 rounded">
                        No cards yet
                    </div>
                </div>
            @endforeach
        </div>

        <form wire:submit="addList" class="w-72 flex-shrink-0">
            <input wire:model="newListName" type="text" placeholder="+ Add a list"
                class="w-full rounded-lg bg-white/90 border-transparent text-sm px-3 py-2 placeholder-gray-500 focus:bg-white focus:border-blue-500 focus:ring-blue-500">
            @error('newListName') <p class="mt-1 text-xs text-red-100 bg-red-600/80 rounded px-2 py-1">{{ $message }}</p> @enderror
        </form>
    </div>
</div>
