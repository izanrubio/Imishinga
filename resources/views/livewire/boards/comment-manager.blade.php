@php
    $deleting = $confirmingDeleteId ? $comments->firstWhere('id', $confirmingDeleteId) : null;
@endphp

<div class="flex flex-col gap-3">
    <form wire:submit="addComment" class="flex flex-col items-end gap-2">
        <label class="sr-only" for="new-comment-{{ $card->id }}">Comment</label>
        <textarea id="new-comment-{{ $card->id }}" wire:model="content" rows="2" placeholder="Write a comment"
            @error('content') aria-invalid="true" aria-describedby="new-comment-{{ $card->id }}-msg" @enderror
            @class([
                'w-full rounded-lg border bg-surface px-3 py-2 text-sm placeholder:text-ink-subtle',
                'border-danger ring-2 ring-danger-soft' => $errors->has('content'),
                'border-border-strong' => ! $errors->has('content'),
            ])></textarea>
        @error('content')
            <p id="new-comment-{{ $card->id }}-msg" class="flex w-full items-center gap-1.5 text-sm text-danger-ink"><x-icon name="alert-circle" class="size-4" />{{ $message }}</p>
        @enderror
        <x-button type="submit" target="addComment" loading-text="Adding…">Comment</x-button>
    </form>

    @if ($comments->isNotEmpty())
        <ul class="flex flex-col gap-4">
            @foreach ($comments as $comment)
                <li wire:key="comment-{{ $comment->id }}" class="flex gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm">
                            <span class="font-medium">{{ $comment->user->name }}</span>
                            <span class="text-xs text-ink-subtle">{{ $comment->created_at->format('j M, H:i') }}</span>
                        </p>
                        <p class="mt-0.5 whitespace-pre-line break-words text-sm text-ink-muted">{{ $comment->content }}</p>
                    </div>
                    @if ($comment->user_id === auth()->id())
                        <x-icon-button variant="danger" class="shrink-0" aria-label="Delete comment" wire:click="confirmDelete({{ $comment->id }})">
                            <x-icon name="trash" />
                        </x-icon-button>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if ($deleting)
        <x-confirm-dialog label="Delete comment" onClose="$wire.cancelDelete()">
            <h3 class="mt-3 text-lg font-semibold tracking-tight">Delete this comment?</h3>
            <p class="mt-1 text-sm text-ink-muted">The comment will be permanently deleted.</p>
            <div class="mt-5 flex flex-wrap justify-end gap-2">
                <x-button variant="secondary" wire:click="cancelDelete">Cancel</x-button>
                <x-button variant="danger" wire:click="deleteComment({{ $deleting->id }})" target="deleteComment" loading-text="Deleting…">
                    Delete comment
                </x-button>
            </div>
        </x-confirm-dialog>
    @endif
</div>
