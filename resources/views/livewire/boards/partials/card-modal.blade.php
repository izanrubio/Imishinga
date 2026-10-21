@php
    $dueTones = ['overdue' => 'danger', 'today' => 'warn', 'completed' => 'ok', 'upcoming' => 'neutral'];
    $dueTone = $editingCard?->dueBadgeTone();
    $isCompleted = (bool) $editingCard?->completed_at;
@endphp

<div
    x-data="{
        initial: { title: @js($cardTitle), description: @js($cardDescription), dueDate: @js($cardDueDate) },
        discarding: false,
        isDirty() {
            return this.$wire.cardTitle !== this.initial.title
                || this.$wire.cardDescription !== this.initial.description
                || this.$wire.cardDueDate !== this.initial.dueDate;
        },
        attemptClose() {
            if (document.querySelector('[role=alertdialog]')) { return; }
            if (this.isDirty()) { setTimeout(() => { this.discarding = true; }); return; }
            $wire.closeCardModal();
        },
    }"
    x-init="$nextTick(() => $el.querySelector('#card-title')?.focus())"
>
    <form id="save-card-form" wire:submit="saveCard" class="hidden" aria-hidden="true"></form>

    <x-modal label="Card details" class="max-w-2xl" onClose="attemptClose()">
        <x-slot:header>
            <div class="flex shrink-0 items-start justify-between gap-3 px-5 pt-4">
                <div class="min-w-0 flex-1">
                    <label class="sr-only" for="card-title">Card title</label>
                    <input id="card-title" form="save-card-form" wire:model="cardTitle" type="text"
                        @error('cardTitle') aria-invalid="true" aria-describedby="card-title-msg" @enderror
                        @class([
                            'w-full rounded-md border bg-transparent px-2 py-1 text-lg font-semibold tracking-tight hover:bg-surface-muted focus:bg-surface',
                            'text-ink-subtle line-through' => $isCompleted,
                            'border-danger ring-2 ring-danger-soft' => $errors->has('cardTitle'),
                            'border-transparent focus:border-accent' => ! $errors->has('cardTitle'),
                        ])>
                    @error('cardTitle')
                        <p id="card-title-msg" class="mt-1 flex items-center gap-1.5 px-2 text-sm text-danger-ink">
                            <x-icon name="alert-circle" class="size-4" />
                            {{ $message }}
                        </p>
                    @enderror
                    @if ($editingCard?->list)
                        <p class="px-2 text-sm text-ink-muted">in list <span class="font-medium text-ink">{{ $editingCard->list->name }}</span></p>
                    @endif
                </div>
                <x-icon-button aria-label="Close" class="shrink-0" x-on:click="attemptClose()">
                    <x-icon name="x" />
                </x-icon-button>
            </div>
        </x-slot:header>

        <label class="flex min-h-10 w-fit cursor-pointer items-center gap-2.5 text-sm font-medium">
            <input id="card-completed-toggle" type="checkbox" wire:click="toggleCompleted({{ $editingCard?->id }})"
                @checked($isCompleted) class="size-5 accent-ok">
            Mark as complete
        </label>

        <div class="flex flex-col gap-2">
            <h3 class="text-sm font-semibold">Labels</h3>
            <div class="flex flex-wrap items-center gap-2">
                @forelse ($labels as $label)
                    @php $assigned = (bool) $editingCard?->labels->contains($label->id); @endphp
                    <button type="button" aria-pressed="{{ $assigned ? 'true' : 'false' }}"
                        wire:click="toggleCardLabel({{ $editingCard?->id }}, {{ $label->id }})"
                        @class([
                            'inline-flex h-8 items-center gap-1.5 rounded-md px-3 max-sm:h-10 text-sm font-medium text-ink',
                            $label->colorClass(),
                            'ring-2 ring-ink ring-offset-1' => $assigned,
                            'opacity-60 hover:opacity-100' => ! $assigned,
                        ])>
                        @if ($assigned)
                            <x-icon name="check" class="size-3.5" />
                        @endif
                        {{ $label->name }}
                    </button>
                @empty
                    <p class="text-sm text-ink-subtle">No labels yet. Create some from the board's Labels button.</p>
                @endforelse
            </div>
        </div>

        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-semibold">Due date</h3>
                @if ($dueTone)
                    <x-card-badge :icon="$dueTone === 'completed' ? 'check' : 'calendar'" :tone="$dueTones[$dueTone]">{{ $editingCard->due_date->format('j M') }}</x-card-badge>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <label class="sr-only" for="card-due-date">Due date</label>
                <div class="relative w-64 max-w-full">
                    <span class="pointer-events-none absolute left-3 top-3 text-ink-muted"><x-icon name="calendar" /></span>
                    <input id="card-due-date" form="save-card-form" wire:model="cardDueDate" type="datetime-local"
                        @error('cardDueDate') aria-invalid="true" aria-describedby="card-due-date-msg" @enderror
                        @class([
                            'h-10 w-full rounded-lg border bg-surface pl-10 pr-3 text-sm placeholder:text-ink-subtle',
                            'border-danger ring-2 ring-danger-soft' => $errors->has('cardDueDate'),
                            'border-border-strong' => ! $errors->has('cardDueDate'),
                        ])>
                </div>
                <x-button variant="ghost" wire:click="clearDueDate">Clear</x-button>
            </div>
            @error('cardDueDate')
                <p id="card-due-date-msg" class="flex items-center gap-1.5 text-sm text-danger-ink"><x-icon name="alert-circle" class="size-4" />{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-col gap-2">
            <h3 class="text-sm font-semibold">Description</h3>
            <label class="sr-only" for="card-description">Description</label>
            <textarea id="card-description" form="save-card-form" wire:model="cardDescription" rows="3"
                @error('cardDescription') aria-invalid="true" aria-describedby="card-description-msg" @enderror
                @class([
                    'w-full rounded-lg border bg-surface px-3 py-2 text-sm placeholder:text-ink-subtle',
                    'border-danger ring-2 ring-danger-soft' => $errors->has('cardDescription'),
                    'border-border-strong' => ! $errors->has('cardDescription'),
                ])></textarea>
            @error('cardDescription')
                <p id="card-description-msg" class="flex items-center gap-1.5 text-sm text-danger-ink"><x-icon name="alert-circle" class="size-4" />{{ $message }}</p>
            @enderror
        </div>

        @if ($editingCard)
            <div class="flex flex-col gap-3">
                <h3 class="text-sm font-semibold">Checklists</h3>
                <livewire:boards.checklist-manager :card="$editingCard" :key="'checklist-manager-'.$editingCard->id" />
            </div>

            <div class="flex flex-col gap-2">
                <h3 class="text-sm font-semibold">Attachments</h3>
                <livewire:boards.attachment-manager :card="$editingCard" :key="'attachment-manager-'.$editingCard->id" />
            </div>

            <div class="flex flex-col gap-3">
                <h3 class="text-sm font-semibold">Comments</h3>
                <livewire:boards.comment-manager :card="$editingCard" :key="'comment-manager-'.$editingCard->id" />
            </div>
        @endif

        @if ($confirmingDeleteCard && $editingCard)
            <x-confirm-dialog label="Delete card" onClose="$wire.cancelDeleteCard()">
                <h3 class="mt-3 text-lg font-semibold tracking-tight">Delete &ldquo;{{ $editingCard->title }}&rdquo;?</h3>
                <p class="mt-1 text-sm text-ink-muted">The card, its checklist, comments and attachments will be permanently deleted.</p>
                <div class="mt-5 flex flex-wrap justify-end gap-2">
                    <x-button variant="secondary" wire:click="cancelDeleteCard">Cancel</x-button>
                    <x-button variant="danger" wire:click="deleteCard({{ $editingCard->id }})" target="deleteCard" loading-text="Deleting…">
                        Delete card
                    </x-button>
                </div>
            </x-confirm-dialog>
        @endif

        <template x-if="discarding">
            <div>
            <x-confirm-dialog label="Discard unsaved changes" tone="warn" onClose="discarding = false">
                <h3 class="mt-3 text-lg font-semibold tracking-tight">Discard unsaved changes?</h3>
                <p class="mt-1 text-sm text-ink-muted">You have edits that have not been saved. If you leave now they will be lost.</p>
                <div class="mt-5 flex flex-wrap justify-end gap-2">
                    <x-button variant="secondary" x-on:click="discarding = false">Keep editing</x-button>
                    <x-button variant="danger" x-on:click="discarding = false; $wire.closeCardModal()">Discard changes</x-button>
                </div>
            </x-confirm-dialog>
            </div>
        </template>

        <x-slot:footer>
            <div class="mr-auto">
                <button type="button" aria-label="Delete card" wire:click="confirmDeleteCard"
                    class="inline-flex h-10 items-center justify-center gap-2 whitespace-nowrap rounded-lg px-4 text-sm font-medium text-danger-ink transition-colors hover:bg-danger-soft max-sm:w-10 max-sm:px-0">
                    <x-icon name="trash" />
                    <span class="max-sm:sr-only">Delete card</span>
                </button>
            </div>
            <x-button variant="ghost" x-on:click="attemptClose()">Cancel</x-button>
            <x-button type="submit" form="save-card-form" target="saveCard" loading-text="Saving…">Save changes</x-button>
        </x-slot:footer>
    </x-modal>
</div>
