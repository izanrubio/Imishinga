<?php

namespace Tests\Feature;

use App\Enums\BoardColor;
use App\Livewire\Boards\Index;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class BoardsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_state_is_shown_when_the_user_has_no_boards(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSee('No boards yet');
    }

    public function test_boards_are_not_shown_when_empty(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertViewHas('boards', fn ($boards) => $boards->isEmpty());
    }

    public function test_user_can_create_a_board_with_a_valid_color(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('name', 'Web freelance')
            ->set('description', 'Client sites')
            ->set('background_color', BoardColor::Clay->value)
            ->call('save');

        $this->assertDatabaseHas('boards', [
            'user_id' => $user->id,
            'name' => 'Web freelance',
            'background_color' => 'clay',
        ]);
    }

    public function test_only_the_eight_color_keys_are_accepted(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('name', 'Bad color board')
            ->set('background_color', 'not-a-real-color')
            ->call('save')
            ->assertHasErrors(['background_color']);

        $this->assertDatabaseMissing('boards', ['name' => 'Bad color board']);
    }

    public function test_unknown_stored_color_falls_back_to_slate_when_read(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();

        // bypass the enum validation to simulate a legacy/garbage stored value
        DB::table('boards')->where('id', $board->id)->update(['background_color' => '#0079BF']);

        $this->assertSame(BoardColor::Slate, $board->fresh()->colorEnum());
        $this->assertSame('bg-board-slate', $board->fresh()->colorClass());
    }

    public function test_null_stored_color_falls_back_to_slate_when_read(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();

        DB::table('boards')->where('id', $board->id)->update(['background_color' => null]);

        $this->assertSame(BoardColor::Slate, $board->fresh()->colorEnum());
    }

    public function test_delete_dialog_shows_correct_list_and_card_counts(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $listA = BoardList::factory()->for($board)->create();
        $listB = BoardList::factory()->for($board)->create();
        Card::factory()->for($listA, 'list')->create();
        Card::factory()->for($listA, 'list')->create();
        Card::factory()->for($listB, 'list')->create();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('confirmDelete', $board->id, 2, 3)
            ->assertSet('deletingListsCount', 2)
            ->assertSet('deletingCardsCount', 3)
            ->assertSee('2 lists')
            ->assertSee('3 cards');
    }

    public function test_confirming_delete_removes_the_board_and_its_data(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();
        $card = Card::factory()->for($list, 'list')->create();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('confirmDelete', $board->id, 1, 1)
            ->call('deleteBoard', $board->id);

        $this->assertDatabaseMissing('boards', ['id' => $board->id]);
        $this->assertDatabaseMissing('lists', ['id' => $list->id]);
        $this->assertDatabaseMissing('cards', ['id' => $card->id]);
    }

    public function test_cancel_delete_closes_the_dialog_without_deleting(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('confirmDelete', $board->id, 0, 0)
            ->call('cancelDelete')
            ->assertSet('deleting', null);

        $this->assertDatabaseHas('boards', ['id' => $board->id]);
    }

    public function test_boards_list_counts_avoid_n_plus_one_queries(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 3) as $i) {
            $board = Board::factory()->for($user)->create();
            $list = BoardList::factory()->for($board)->create();
            Card::factory()->for($list, 'list')->create();
        }

        DB::enableQueryLog();
        Livewire::actingAs($user)->test(Index::class);
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // a handful of fixed queries regardless of board count, not one per board
        $this->assertLessThan(10, $queryCount);
    }
}
