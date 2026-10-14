<div class="space-y-4">
    @foreach ($checklists as $checklist)
        @php
            $total = $checklist->items->count();
            $done = $checklist->items->where('is_done', true)->count();
            $percent = $total > 0 ? intval(round($done / $total * 100)) : 0;
        @endphp
        <div wire:key="checklist-{{ $checklist->id }}" class="border border-gray-200 rounded-md p-3">
            <div class="flex items-center justify-between gap-2 mb-2" x-data="{ editing: false, title: @js($checklist->title) }">
                <template x-if="!editing">
                    <h3 x-on:click="editing = true" class="font-medium text-sm text-gray-800 cursor-text hover:bg-gray-100 rounded px-1 py-0.5 flex-1" x-text="title"></h3>
                </template>
                <template x-if="editing">
                    <input type="text" x-model="title" x-ref="input"
                        x-on:blur="editing = false; $wire.renameChecklist({{ $checklist->id }}, title)"
                        x-on:keydown.enter="editing = false; $wire.renameChecklist({{ $checklist->id }}, title)"
                        x-on:keydown.escape="editing = false; title = @js($checklist->title)"
                        x-init="$watch('editing', value => value && $nextTick(() => $refs.input.focus()))"
                        class="flex-1 rounded border-gray-300 text-sm px-1 py-0.5">
                </template>
                <button type="button"
                    x-on:click="if (confirm('Delete this checklist and its items?')) { $wire.deleteChecklist({{ $checklist->id }}) }"
                    class="text-gray-400 hover:text-red-600 text-sm flex-shrink-0">&times;</button>
            </div>

            <div class="flex items-center gap-2 mb-3">
                <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                    @if ($total > 0)
                        <div class="h-full bg-blue-500" style="width: {{ $percent }}%"></div>
                    @endif
                </div>
                <span class="text-xs text-gray-500 flex-shrink-0">{{ $done }}/{{ $total }}</span>
            </div>

            <div class="space-y-1 mb-2">
                @foreach ($checklist->items as $item)
                    <div wire:key="item-{{ $item->id }}" class="flex items-center gap-2" x-data="{ editing: false, content: @js($item->content) }">
                        <input type="checkbox" wire:click="toggleItem({{ $item->id }})" @checked($item->is_done)
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 flex-shrink-0">

                        <template x-if="!editing">
                            <p x-on:click="editing = true"
                                @class([
                                    'flex-1 text-sm cursor-text hover:bg-gray-100 rounded px-1 py-0.5',
                                    'text-gray-400 line-through' => $item->is_done,
                                    'text-gray-700' => ! $item->is_done,
                                ])
                                x-text="content"></p>
                        </template>
                        <template x-if="editing">
                            <input type="text" x-model="content" x-ref="itemInput"
                                x-on:blur="editing = false; $wire.updateItemContent({{ $item->id }}, content)"
                                x-on:keydown.enter="editing = false; $wire.updateItemContent({{ $item->id }}, content)"
                                x-on:keydown.escape="editing = false; content = @js($item->content)"
                                x-init="$watch('editing', value => value && $nextTick(() => $refs.itemInput.focus()))"
                                class="flex-1 rounded border-gray-300 text-sm px-1 py-0.5">
                        </template>

                        <button type="button" wire:click="deleteItem({{ $item->id }})"
                            class="text-gray-300 hover:text-red-600 text-xs flex-shrink-0">&times;</button>
                    </div>
                @endforeach
            </div>

            <form wire:submit="addItem({{ $checklist->id }})">
                <input wire:model="newItemContent.{{ $checklist->id }}" type="text" placeholder="+ Add an item"
                    class="w-full rounded-md border-gray-200 text-sm px-2 py-1 placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500">
            </form>
        </div>
    @endforeach

    <form wire:submit="addChecklist" class="flex gap-2">
        <input wire:model="newChecklistTitle" type="text" placeholder="Checklist title"
            class="flex-1 rounded-md border-gray-300 text-sm px-2 py-1.5 focus:border-blue-500 focus:ring-blue-500">
        <button type="submit" class="px-3 py-1.5 text-sm rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 flex-shrink-0">
            Add checklist
        </button>
    </form>
</div>
