<div class="space-y-3">
    <form wire:submit="addComment" class="space-y-2">
        <textarea wire:model="content" rows="2" placeholder="Write a comment…"
            class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
        @error('content') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        <button type="submit" class="px-3 py-1.5 text-sm rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200">
            Comment
        </button>
    </form>

    <div class="space-y-3">
        @forelse ($comments as $comment)
            <div wire:key="comment-{{ $comment->id }}" class="text-sm border-t border-gray-100 pt-2">
                <div class="flex items-center justify-between gap-2">
                    <p class="font-medium text-gray-800">{{ $comment->user->name }}</p>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400">{{ $comment->created_at->format('j M Y, H:i') }}</span>
                        @if ($comment->user_id === auth()->id())
                            <button type="button"
                                x-on:click="if (confirm('Delete this comment?')) { $wire.deleteComment({{ $comment->id }}) }"
                                class="text-xs text-red-500 hover:text-red-700">Delete</button>
                        @endif
                    </div>
                </div>
                <p class="text-gray-700 mt-1 whitespace-pre-line">{{ $comment->content }}</p>
            </div>
        @empty
            <p class="text-xs text-gray-400">No comments yet.</p>
        @endforelse
    </div>
</div>
