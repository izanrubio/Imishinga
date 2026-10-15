<div
    x-data="{
        initial: { title: @js($cardTitle), description: @js($cardDescription), dueDate: @js($cardDueDate) },
        isDirty() {
            return this.$wire.cardTitle !== this.initial.title
                || this.$wire.cardDescription !== this.initial.description
                || this.$wire.cardDueDate !== this.initial.dueDate;
        },
        attemptClose() {
            if (this.isDirty() && ! confirm('Discard unsaved changes?')) { return; }
            $wire.closeCardModal();
        },
    }"
    x-init="
        document.body.style.overflow = 'hidden';
        $nextTick(() => $refs.titleInput && $refs.titleInput.focus());
        return () => { document.body.style.overflow = ''; };
    "
    x-on:keydown.escape.window="attemptClose()"
    class="fixed inset-0 bg-black/40 z-50 flex md:items-center md:justify-center md:p-4"
>
    <form id="save-card-form" wire:submit="saveCard" class="hidden" aria-hidden="true"></form>

    <div x-on:click.outside="attemptClose()"
        class="bg-white w-full h-full md:h-auto md:max-h-[90vh] md:w-full md:max-w-4xl md:rounded-lg md:shadow-lg flex flex-col">

        {{-- Header --}}
        <div class="flex items-start justify-between gap-3 px-4 sm:px-6 py-4 border-b border-gray-100 flex-shrink-0">
            <div class="flex-1 min-w-0">
                <input x-ref="titleInput" form="save-card-form" wire:model="cardTitle" type="text"
                    class="w-full text-xl font-semibold text-gray-900 border-0 focus:ring-2 focus:ring-blue-500 rounded-md px-2 -mx-2">
                @if ($editingCard?->list)
                    <p class="text-sm text-gray-500 mt-1 px-2">in list <span class="font-medium">{{ $editingCard->list->name }}</span></p>
                @endif
                @error('cardTitle') <p class="mt-1 text-sm text-red-600 px-2">{{ $message }}</p> @enderror
            </div>
            <button type="button" x-on:click="attemptClose()"
                class="w-10 h-10 -m-2 flex-shrink-0 inline-flex items-center justify-center text-gray-400 hover:text-gray-600">
                &times;
            </button>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-[2fr_1fr] gap-6
                [grid-template-areas:'description'_'sidebar'_'checklists'_'attachments'_'comments']
                md:[grid-template-areas:'description_sidebar'_'checklists_sidebar'_'attachments_sidebar'_'comments_sidebar']">

                {{-- Description (main) --}}
                <div class="[grid-area:description]">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea form="save-card-form" wire:model="cardDescription" rows="4"
                        class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    @error('cardDescription') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Sidebar: labels, due date, completed, delete --}}
                <div class="[grid-area:sidebar] space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Labels</label>
                        <div class="flex flex-wrap gap-2">
                            @forelse ($labels as $label)
                                @php $assigned = $editingCard?->labels->contains($label->id); @endphp
                                <button type="button" wire:click="toggleCardLabel({{ $editingCard?->id }}, {{ $label->id }})"
                                    class="text-xs px-2 min-h-[32px] inline-flex items-center rounded-full border {{ $assigned ? 'text-white border-transparent' : 'text-gray-600 border-gray-300 hover:border-gray-400' }}"
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
                            <input form="save-card-form" wire:model="cardDueDate" type="datetime-local"
                                class="flex-1 min-w-0 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <button type="button" wire:click="clearDueDate"
                                class="px-3 min-h-[40px] text-sm rounded-md text-gray-600 hover:bg-gray-100 flex-shrink-0">
                                Clear
                            </button>
                        </div>
                        @error('cardDueDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-2">
                        <label for="card-completed-toggle" class="inline-flex items-center justify-center w-10 h-10 -m-2 flex-shrink-0">
                            <input id="card-completed-toggle" type="checkbox" wire:click="toggleCompleted({{ $editingCard?->id }})"
                                @checked($editingCard?->completed_at)
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </label>
                        <label for="card-completed-toggle" class="text-sm text-gray-700 cursor-pointer">Completed</label>
                    </div>

                    <div class="border-t border-gray-100 pt-4">
                        <button type="button"
                            x-on:click="if (confirm('Delete this card?')) { $wire.deleteCard({{ $editingCard?->id }}) }"
                            class="px-3 min-h-[40px] text-sm rounded-md text-red-600 hover:bg-red-50 w-full text-left">
                            Delete card
                        </button>
                    </div>
                </div>

                {{-- Checklists (main) --}}
                @if ($editingCard)
                    <div class="[grid-area:checklists]">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Checklists</label>
                        <livewire:boards.checklist-manager :card="$editingCard" :key="'checklist-manager-'.$editingCard->id" />
                    </div>

                    <div class="[grid-area:attachments]">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Attachments</label>
                        <livewire:boards.attachment-manager :card="$editingCard" :key="'attachment-manager-'.$editingCard->id" />
                    </div>

                    <div class="[grid-area:comments]">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Comments</label>
                        <livewire:boards.comment-manager :card="$editingCard" :key="'comment-manager-'.$editingCard->id" />
                    </div>
                @endif
            </div>
        </div>

        {{-- Sticky footer --}}
        <div class="flex justify-end gap-2 px-4 sm:px-6 py-3 border-t border-gray-100 flex-shrink-0">
            <button type="button" x-on:click="attemptClose()"
                class="px-4 min-h-[40px] text-sm rounded-md text-gray-700 hover:bg-gray-100">
                Cancel
            </button>
            <button type="submit" form="save-card-form" class="px-4 min-h-[40px] text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">
                Save
            </button>
        </div>
    </div>
</div>
