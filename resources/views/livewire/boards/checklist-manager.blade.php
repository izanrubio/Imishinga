@php
    $deleting = $confirmingDeleteChecklistId ? $checklists->firstWhere('id', $confirmingDeleteChecklistId) : null;
@endphp

<div class="flex flex-col gap-3">
    @foreach ($checklists as $checklist)
        @php
            $total = $checklist->items->count();
            $done = $checklist->items->where('is_done', true)->count();
            $percent = $total > 0 ? intval(round($done / $total * 100)) : 0;
        @endphp
        <div wire:key="checklist-{{ $checklist->id }}" class="flex flex-col gap-2 rounded-xl border border-border p-3">
            <div class="flex items-center gap-1" x-data="{ title: @js($checklist->title), saved: @js($checklist->title) }">
                <label class="sr-only" for="checklist-title-{{ $checklist->id }}">Checklist title</label>
                <input id="checklist-title-{{ $checklist->id }}" type="text" x-model="title"
                    x-on:blur="if (title.trim() === '') { title = saved } else if (title !== saved) { saved = title; $wire.renameChecklist({{ $checklist->id }}, title) }"
                    x-on:keydown.enter.prevent="$el.blur()"
                    x-on:keydown.escape.stop="title = saved; $el.blur()"
                    class="min-w-0 flex-1 rounded-md border border-transparent bg-transparent px-2 py-1.5 text-sm font-semibold hover:bg-surface-muted focus:border-accent focus:bg-surface">
                <span class="px-1 text-xs font-medium text-ink-muted">{{ $done }}/{{ $total }}</span>
                <x-icon-button variant="danger" class="shrink-0" aria-label="Delete checklist {{ $checklist->title }}" wire:click="confirmDeleteChecklist({{ $checklist->id }})">
                    <x-icon name="trash" />
                </x-icon-button>
            </div>

            <div class="h-1.5 w-full overflow-hidden rounded-full bg-surface-muted" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                <div class="h-full rounded-full bg-accent" style="width: {{ $percent }}%"></div>
            </div>

            @if ($checklist->items->isEmpty())
                <p class="rounded-lg border border-dashed border-border-strong px-3 py-3 text-center text-sm text-ink-subtle">No items yet</p>
            @else
                <ul class="flex flex-col">
                    @foreach ($checklist->items as $item)
                        <li wire:key="item-{{ $item->id }}" class="flex min-h-10 items-center gap-2" x-data="{ editing: false, content: @js($item->content) }">
                            <label class="-ml-3 -mr-3 flex size-10 shrink-0 cursor-pointer items-center justify-center">
                                <input type="checkbox" wire:click="toggleItem({{ $item->id }})" @checked($item->is_done)
                                    aria-label="{{ $item->content }}" class="size-4 accent-accent">
                            </label>

                            <template x-if="!editing">
                                <button type="button" x-on:click="editing = true" x-text="content"
                                    @class([
                                        'min-h-10 min-w-0 flex-1 rounded-md px-1 py-2 text-left text-sm hover:bg-surface-muted',
                                        'text-ink-subtle line-through' => $item->is_done,
                                    ])></button>
                            </template>
                            <template x-if="editing">
                                <input type="text" aria-label="Item text" x-model="content" x-ref="itemInput"
                                    x-on:blur="editing = false; $wire.updateItemContent({{ $item->id }}, content)"
                                    x-on:keydown.enter="editing = false; $wire.updateItemContent({{ $item->id }}, content)"
                                    x-on:keydown.escape.stop="editing = false; content = @js($item->content)"
                                    x-init="$watch('editing', value => value && $nextTick(() => $refs.itemInput.focus()))"
                                    class="min-w-0 flex-1 rounded-md border border-accent bg-surface px-2 py-1 text-sm outline-none ring-2 ring-accent-soft">
                            </template>

                            <x-icon-button variant="danger" class="shrink-0" aria-label="Delete item" wire:click="deleteItem({{ $item->id }})">
                                <x-icon name="trash" />
                            </x-icon-button>
                        </li>
                    @endforeach
                </ul>
            @endif

            <form wire:submit="addItem({{ $checklist->id }})" class="flex gap-2">
                <label class="sr-only" for="new-item-{{ $checklist->id }}">New item</label>
                <input id="new-item-{{ $checklist->id }}" wire:model="newItemContent.{{ $checklist->id }}" type="text" placeholder="Add an item" autocomplete="off"
                    class="h-10 w-full rounded-lg border border-border-strong bg-surface px-3 text-sm placeholder:text-ink-subtle">
                <x-button variant="secondary" type="submit" target="addItem({{ $checklist->id }})" loading-text="Adding…">
                    <x-icon name="plus" />
                    Add
                </x-button>
            </form>
        </div>
    @endforeach

    <x-button variant="secondary" class="w-fit" wire:click="addChecklist" target="addChecklist" loading-text="Adding…">
        <x-icon name="plus" />
        Add checklist
    </x-button>

    @if ($deleting)
        <x-confirm-dialog label="Delete checklist" onClose="$wire.cancelDeleteChecklist()">
            <h3 class="mt-3 text-lg font-semibold tracking-tight">Delete &ldquo;{{ $deleting->title }}&rdquo;?</h3>
            <p class="mt-1 text-sm text-ink-muted">
                This checklist and its {{ $deleting->items->count() }} {{ Str::plural('item', $deleting->items->count()) }} will be permanently deleted.
            </p>
            <div class="mt-5 flex flex-wrap justify-end gap-2">
                <x-button variant="secondary" wire:click="cancelDeleteChecklist">Cancel</x-button>
                <x-button variant="danger" wire:click="deleteChecklist({{ $deleting->id }})" target="deleteChecklist" loading-text="Deleting…">
                    Delete checklist
                </x-button>
            </div>
        </x-confirm-dialog>
    @endif
</div>
