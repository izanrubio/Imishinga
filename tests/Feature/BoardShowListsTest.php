<?php

namespace Tests\Feature;

use App\Livewire\Boards\Show;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Label;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class BoardShowListsTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_list_dialog_names_the_list_and_counts_its_cards(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create(['name' => 'Backlog']);
        Card::factory()->count(4)->for($list, 'list')->create();

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->call('confirmDeleteList', $list->id)
            ->assertSeeHtml('Delete &ldquo;Backlog&rdquo;?')
            ->assertSee('This list and its 4 cards will be permanently deleted.')
            ->assertSee('Delete list');

        $this->assertDatabaseHas('lists', ['id' => $list->id]);
    }

    public function test_delete_list_dialog_is_singular_for_one_card(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();
        Card::factory()->for($list, 'list')->create();

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->call('confirmDeleteList', $list->id)
            ->assertSee('This list and its 1 card will be permanently deleted.');
    }

    public function test_cancel_closes_the_dialog_and_delete_removes_the_list(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();

        $component = Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->call('confirmDeleteList', $list->id)
            ->call('cancelDeleteList')
            ->assertSet('confirmingDeleteListId', null)
            ->assertDontSee('will be permanently deleted');

        $this->assertDatabaseHas('lists', ['id' => $list->id]);

        $component->call('confirmDeleteList', $list->id)
            ->call('deleteList', $list->id)
            ->assertSet('confirmingDeleteListId', null);

        $this->assertDatabaseMissing('lists', ['id' => $list->id]);
    }

    public function test_confirming_delete_of_a_foreign_list_404s(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $foreign = BoardList::factory()->for(Board::factory()->create())->create();

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->call('confirmDeleteList', $foreign->id)
            ->assertStatus(404);
    }

    public function test_card_count_per_list_is_shown_and_does_not_add_queries(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $first = BoardList::factory()->for($board)->create(['position' => 0, 'name' => 'First']);
        Card::factory()->count(2)->for($first, 'list')->create();

        $render = function () use ($user, $board): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            Livewire::actingAs($user)->test(Show::class, ['board' => $board]);

            return count(DB::getQueryLog());
        };

        $few = $render();

        foreach (range(1, 4) as $position) {
            $list = BoardList::factory()->for($board)->create(['position' => $position]);
            Card::factory()->count(5)->for($list, 'list')->create();
        }

        $this->assertSame($few, $render());

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->assertSeeHtmlInOrder(['aria-label="Drag list First"', '>2</span>']);
    }

    public function test_compact_card_shows_label_bars_through_the_enum_and_hides_zero_badges(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();
        $card = Card::factory()->for($list, 'list')->create(['title' => 'Plain card', 'description' => null]);
        $card->labels()->attach([
            Label::factory()->for($board)->create(['color' => 'red'])->id,
            Label::factory()->for($board)->create(['color' => '#legacy'])->id,
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->assertSeeHtml('bg-label-red')
            ->assertSeeHtml('bg-label-blue')
            ->assertDontSeeHtml('mt-2 flex flex-wrap items-center gap-x-2');
    }
}
