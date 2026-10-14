<?php

namespace Tests\Feature;

use App\Livewire\Boards\CommentManager;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CommentManagerTest extends TestCase
{
    use RefreshDatabase;

    private function makeCard(User $user): Card
    {
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();

        return Card::factory()->for($list, 'list')->create();
    }

    public function test_user_can_add_a_comment(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);

        Livewire::actingAs($user)
            ->test(CommentManager::class, ['card' => $card])
            ->set('content', 'Looks good to me')
            ->call('addComment')
            ->assertSet('content', '');

        $this->assertDatabaseHas('comments', [
            'card_id' => $card->id,
            'user_id' => $user->id,
            'content' => 'Looks good to me',
        ]);
    }

    public function test_adding_a_blank_comment_fails_validation(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);

        Livewire::actingAs($user)
            ->test(CommentManager::class, ['card' => $card])
            ->set('content', '')
            ->call('addComment')
            ->assertHasErrors(['content' => 'required']);

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_author_can_delete_their_own_comment(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);
        $comment = Comment::factory()->for($card)->for($user)->create();

        Livewire::actingAs($user)
            ->test(CommentManager::class, ['card' => $card])
            ->call('deleteComment', $comment->id);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_non_author_cannot_delete_someone_elses_comment(): void
    {
        $owner = User::factory()->create();
        $card = $this->makeCard($owner);
        $otherAuthor = User::factory()->create();
        $comment = Comment::factory()->for($card)->for($otherAuthor)->create();

        // the board owner can see the card (and thus the comment) but did not author it
        Livewire::actingAs($owner)
            ->test(CommentManager::class, ['card' => $card])
            ->call('deleteComment', $comment->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }

    public function test_comment_actions_404_when_comment_belongs_to_a_different_card(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);

        $otherCard = $this->makeCard(User::factory()->create());
        $otherComment = Comment::factory()->for($otherCard)->create();

        Livewire::actingAs($user)
            ->test(CommentManager::class, ['card' => $card])
            ->call('deleteComment', $otherComment->id)
            ->assertStatus(404);
    }

    public function test_comment_manager_404s_for_a_card_on_a_board_owned_by_another_user(): void
    {
        $user = User::factory()->create();
        $otherUsersCard = $this->makeCard(User::factory()->create());

        Livewire::actingAs($user)
            ->test(CommentManager::class, ['card' => $otherUsersCard])
            ->assertStatus(404);
    }
}
