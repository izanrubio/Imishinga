<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Your boards</h1>
        <button wire:click="createNew" class="bg-blue-600 text-white rounded-md px-4 py-2 text-sm font-medium hover:bg-blue-700">
            + Create board
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @forelse ($boards as $board)
            <div wire:key="board-{{ $board->id }}" class="group relative rounded-lg overflow-hidden shadow-sm border border-gray-200 bg-white">
                <a href="{{ route('boards.show', $board) }}" class="block">
                    <div class="h-20" style="background-color: {{ $board->background_color ?? '#0079BF' }}"></div>
                    <div class="p-4">
                        <h2 class="font-medium text-gray-900 truncate">{{ $board->name }}</h2>
                        @if ($board->description)
                            <p class="text-sm text-gray-500 truncate mt-1">{{ $board->description }}</p>
                        @endif
                    </div>
                </a>

                <div class="absolute top-2 right-2 hidden group-hover:flex gap-1">
                    <button wire:click="editBoard({{ $board->id }})"
                        class="bg-white/90 rounded p-1 text-xs text-gray-700 hover:bg-white shadow">
                        Edit
                    </button>
                    <button
                        x-data
                        x-on:click="if (confirm('Delete this board and everything in it?')) { $wire.deleteBoard({{ $board->id }}) }"
                        class="bg-white/90 rounded p-1 text-xs text-red-600 hover:bg-white shadow">
                        Delete
                    </button>
                </div>
            </div>
        @empty
            <p class="text-gray-500 col-span-full">No boards yet. Create your first one.</p>
        @endforelse
    </div>

    @if ($showFormModal)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" x-data x-on:keydown.escape.window="$wire.showFormModal = false">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6" x-on:click.outside="$wire.showFormModal = false">
                <h2 class="text-lg font-semibold mb-4">{{ $editing ? 'Edit board' : 'Create board' }}</h2>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input wire:model="name" type="text" autofocus
                            class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea wire:model="description" rows="2"
                            class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Background color</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach (\App\Livewire\Boards\Index::COLORS as $color)
                                <button type="button" wire:click="$set('background_color', '{{ $color }}')"
                                    class="w-8 h-8 rounded-full {{ $background_color === $color ? 'ring-2 ring-offset-2 ring-blue-500' : '' }}"
                                    style="background-color: {{ $color }}"></button>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showFormModal', false)"
                            class="px-4 py-2 text-sm rounded-md text-gray-700 hover:bg-gray-100">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
