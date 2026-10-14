<div>
    <button type="button" wire:click="openModal"
        class="text-sm text-white/90 hover:text-white border border-white/40 rounded-md px-3 py-1.5">
        Labels
    </button>

    @if ($showModal)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" x-data x-on:keydown.escape.window="$wire.closeModal()">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6" x-on:click.outside="$wire.closeModal()">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold">Labels</h2>
                    <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <div class="space-y-2 mb-4 max-h-56 overflow-y-auto">
                    @forelse ($labels as $label)
                        <div wire:key="label-{{ $label->id }}" class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded flex-shrink-0" style="background-color: {{ $label->color }}"></span>
                            <span class="flex-1 text-sm text-gray-700 truncate">{{ $label->name }}</span>
                            <button type="button" wire:click="startEdit({{ $label->id }})"
                                class="text-xs text-gray-500 hover:text-gray-800">Edit</button>
                            <button type="button"
                                x-on:click="if (confirm('Delete this label? It will be removed from all cards.')) { $wire.delete({{ $label->id }}) }"
                                class="text-xs text-red-500 hover:text-red-700">Delete</button>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No labels yet.</p>
                    @endforelse
                </div>

                <form wire:submit="save" class="space-y-3 border-t border-gray-100 pt-4">
                    <p class="text-sm font-medium text-gray-700">{{ $editing ? 'Edit label' : 'New label' }}</p>

                    <div>
                        <input wire:model="name" type="text" placeholder="Label name"
                            class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @foreach (\App\Livewire\Boards\LabelManager::COLORS as $colorOption)
                            <button type="button" wire:click="$set('color', '{{ $colorOption }}')"
                                class="w-7 h-7 rounded-full {{ $color === $colorOption ? 'ring-2 ring-offset-2 ring-blue-500' : '' }}"
                                style="background-color: {{ $colorOption }}"></button>
                        @endforeach
                    </div>

                    <div class="flex justify-end gap-2 pt-1">
                        @if ($editing)
                            <button type="button" wire:click="startCreate"
                                class="px-3 py-1.5 text-sm rounded-md text-gray-600 hover:bg-gray-100">
                                Cancel edit
                            </button>
                        @endif
                        <button type="submit" class="px-4 py-1.5 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">
                            {{ $editing ? 'Save' : 'Add label' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
