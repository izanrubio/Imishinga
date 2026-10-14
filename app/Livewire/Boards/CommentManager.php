<?php

namespace App\Livewire\Boards;

use App\Models\Card;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CommentManager extends Component
{
    public Card $card;

    public string $content = '';

    public function mount(Card $card): void
    {
        abort_unless($card->list->board->user_id === Auth::id(), 404);

        $this->card = $card;
    }

    public function addComment(): void
    {
        $data = $this->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $this->card->comments()->create([
            'user_id' => Auth::id(),
            'content' => $data['content'],
        ]);

        $this->content = '';
        $this->dispatch('comments-updated');
    }

    public function deleteComment(int $commentId): void
    {
        $comment = $this->ownedComment($commentId);

        abort_unless($comment->user_id === Auth::id(), 403);

        $comment->delete();
        $this->dispatch('comments-updated');
    }

    private function ownedComment(int $commentId): Comment
    {
        $comment = $this->card->comments()->whereKey($commentId)->first();

        abort_if(! $comment, 404);

        return $comment;
    }

    public function render()
    {
        return view('livewire.boards.comment-manager', [
            'comments' => $this->card->comments()->with('user')->latest()->get(),
        ]);
    }
}
