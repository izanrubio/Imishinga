<?php

namespace Tests\Feature;

use App\Livewire\Boards\Show;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Label;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class BoardShowCardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_a_card_to_a_list(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->set("newCardTitle.{$list->id}", 'Write tests')
            ->call('addCard', $list->id)
            ->assertSet("newCardTitle.{$list->id}", '');

        $this->assertDatabaseHas('cards', [
            'list_id' => $list->id,
            'title' => 'Write tests',
            'position' => 0,
        ]);
    }

    public function test_adding_a_card_with_blank_title_is_ignored(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->set("newCardTitle.{$list->id}", '   ')
            ->call('addCard', $list->id);

        $this->assertDatabaseCount('cards', 0);
    }

    public function test_user_can_edit_a_card(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();
        $card = Card::factory()->for($list, 'list')->create();

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->call('editCard', $card->id)
            ->assertSet('cardTitle', $card->title)
            ->set('cardTitle', 'Updated title')
            ->set('cardDescription', 'Updated description')
            ->call('saveCard')
            ->assertSet('showCardModal', false);

        $this->assertDatabaseHas('cards', [
            'id' => $card->id,
            'title' => 'Updated title',
            'description' => 'Updated description',
        ]);
    }

    public function test_user_can_delete_a_card(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();
        $card = Card::factory()->for($list, 'list')->create();

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->call('deleteCard', $card->id);

        $this->assertDatabaseMissing('cards', ['id' => $card->id]);
    }

    public function test_reordering_within_the_same_list_updates_positions(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();
        $cardA = Card::factory()->for($list, 'list')->create(['position' => 0]);
        $cardB = Card::factory()->for($list, 'list')->create(['position' => 1]);
        $cardC = Card::factory()->for($list, 'list')->create(['position' => 2]);

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->call('reorderCards', $cardC->id, $list->id, [$cardC->id, $cardA->id, $cardB->id]);

        $this->assertSame(0, $cardC->fresh()->position);
        $this->assertSame(1, $cardA->fresh()->position);
        $this->assertSame(2, $cardB->fresh()->position);
        $this->assertSame($list->id, $cardC->fresh()->list_id);
    }

    public function test_moving_a_card_to_another_list_updates_list_id_and_resequences_both_lists(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $source = BoardList::factory()->for($board)->create();
        $destination = BoardList::factory()->for($board)->create();

        $moved = Card::factory()->for($source, 'list')->create(['position' => 0]);
        $remainingInSource = Card::factory()->for($source, 'list')->create(['position' => 1]);
        $existingInDestination = Card::factory()->for($destination, 'list')->create(['position' => 0]);

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->call('reorderCards', $moved->id, $destination->id, [$existingInDestination->id, $moved->id]);

        $moved->refresh();
        $this->assertSame($destination->id, $moved->list_id);
        $this->assertSame(1, $moved->position);

        $this->assertSame(0, $existingInDestination->fresh()->position);

        // the remaining card in the source list should be re-sequenced to close the gap
        $this->assertSame(0, $remainingInSource->fresh()->position);
    }

    public function test_quick_added_cards_get_strictly_increasing_positions(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();

        $component = Livewire::actingAs($user)->test(Show::class, ['board' => $board]);

        foreach (['First', 'Second', 'Third'] as $title) {
            $component->set("newCardTitle.{$list->id}", $title)
                ->call('addCard', $list->id);
        }

        $positions = Card::where('list_id', $list->id)->orderBy('id')->pluck('position', 'title');

        // every card must land on a distinct position, in creation order
        $this->assertSame([0, 1, 2], $positions->values()->all());
    }

    public function test_reordering_within_the_same_list_persists_after_a_simulated_reload(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();

        $component = Livewire::actingAs($user)->test(Show::class, ['board' => $board]);

        foreach (['First', 'Second', 'Third'] as $title) {
            $component->set("newCardTitle.{$list->id}", $title)
                ->call('addCard', $list->id);
        }

        $cards = Card::where('list_id', $list->id)->orderBy('position')->orderBy('id')->get();
        [$first, $second, $third] = [$cards[0], $cards[1], $cards[2]];

        // drag "Third" to the top: Third, First, Second
        $component->call('reorderCards', $third->id, $list->id, [$third->id, $first->id, $second->id]);

        // simulate a hard reload: a brand new component instance re-reading from the DB
        $reloaded = Livewire::actingAs($user)->test(Show::class, ['board' => $board]);
        $orderedTitles = $reloaded->viewData('lists')
            ->firstWhere('id', $list->id)
            ->cards
            ->pluck('title')
            ->all();

        $this->assertSame(['Third', 'First', 'Second'], $orderedTitles);
    }

    public function test_cards_with_tied_positions_still_render_in_a_stable_order(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();

        // simulate a position tie (e.g. left over from a stale/duplicate write)
        $cardA = Card::factory()->for($list, 'list')->create(['position' => 0]);
        $cardB = Card::factory()->for($list, 'list')->create(['position' => 0]);

        $orderedTitles = Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->viewData('lists')
            ->firstWhere('id', $list->id)
            ->cards
            ->pluck('title')
            ->all();

        // with a tie on position, the id tiebreaker must keep this deterministic
        $this->assertSame([$cardA->title, $cardB->title], $orderedTitles);
    }

    public function test_user_can_assign_and_unassign_a_label(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();
        $card = Card::factory()->for($list, 'list')->create();
        $label = Label::factory()->for($board)->create();

        $component = Livewire::actingAs($user)->test(Show::class, ['board' => $board]);

        $component->call('toggleCardLabel', $card->id, $label->id);
        $this->assertDatabaseHas('card_label', ['card_id' => $card->id, 'label_id' => $label->id]);

        $component->call('toggleCardLabel', $card->id, $label->id);
        $this->assertDatabaseMissing('card_label', ['card_id' => $card->id, 'label_id' => $label->id]);
    }

    public function test_assigning_a_label_from_a_different_board_404s(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();
        $card = Card::factory()->for($list, 'list')->create();

        $otherBoard = Board::factory()->create();
        $otherLabel = Label::factory()->for($otherBoard)->create();

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->call('toggleCardLabel', $card->id, $otherLabel->id)
            ->assertStatus(404);

        $this->assertDatabaseMissing('card_label', ['card_id' => $card->id, 'label_id' => $otherLabel->id]);
    }

    public function test_user_can_set_and_clear_a_due_date(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();
        $card = Card::factory()->for($list, 'list')->create();

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->call('editCard', $card->id)
            ->set('cardDueDate', '2026-12-25T10:00')
            ->call('saveCard');

        $this->assertSame('2026-12-25 10:00:00', $card->fresh()->due_date->format('Y-m-d H:i:s'));

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->call('editCard', $card->id)
            ->assertSet('cardDueDate', '2026-12-25T10:00')
            ->call('clearDueDate')
            ->call('saveCard');

        $this->assertNull($card->fresh()->due_date);
    }

    public function test_user_can_toggle_completed_without_moving_the_card(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();
        $card = Card::factory()->for($list, 'list')->create(['position' => 3]);

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->call('toggleCompleted', $card->id);

        $card->refresh();
        $this->assertNotNull($card->completed_at);
        $this->assertSame($list->id, $card->list_id);
        $this->assertSame(3, $card->position);

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->call('toggleCompleted', $card->id);

        $this->assertNull($card->fresh()->completed_at);
    }

    public function test_due_badge_tone_reflects_completed_overdue_today_and_upcoming(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();

        $noDueDate = Card::factory()->for($list, 'list')->create(['due_date' => null]);
        $overdue = Card::factory()->for($list, 'list')->create(['due_date' => '2026-06-10 09:00:00']);
        $dueToday = Card::factory()->for($list, 'list')->create(['due_date' => '2026-06-15 18:00:00']);
        $upcoming = Card::factory()->for($list, 'list')->create(['due_date' => '2026-07-01 09:00:00']);
        $completedOverdue = Card::factory()->for($list, 'list')->create([
            'due_date' => '2026-06-01 09:00:00',
            'completed_at' => now(),
        ]);

        $this->assertNull($noDueDate->dueBadgeTone());
        $this->assertSame('overdue', $overdue->dueBadgeTone());
        $this->assertSame('today', $dueToday->dueBadgeTone());
        $this->assertSame('upcoming', $upcoming->dueBadgeTone());
        $this->assertSame('completed', $completedOverdue->dueBadgeTone());

        Carbon::setTestNow();
    }

    public function test_board_view_exposes_checklist_progress_counts_without_n_plus_one(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();

        $cardWithProgress = Card::factory()->for($list, 'list')->create();
        $checklist = Checklist::factory()->for($cardWithProgress)->create();
        ChecklistItem::factory()->for($checklist)->create(['is_done' => true]);
        ChecklistItem::factory()->for($checklist)->create(['is_done' => true]);
        ChecklistItem::factory()->for($checklist)->create(['is_done' => false]);

        $cardWithoutChecklists = Card::factory()->for($list, 'list')->create();

        DB::enableQueryLog();

        $lists = Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->viewData('lists');

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $cards = $lists->firstWhere('id', $list->id)->cards;
        $withProgress = $cards->firstWhere('id', $cardWithProgress->id);
        $withoutChecklists = $cards->firstWhere('id', $cardWithoutChecklists->id);

        $this->assertSame(3, $withProgress->checklist_items_count);
        $this->assertSame(2, $withProgress->checklist_items_done_count);
        $this->assertSame(0, $withoutChecklists->checklist_items_count);

        // a handful of fixed queries regardless of card count, not one per card
        $this->assertLessThan(10, $queryCount);
    }

    public function test_card_actions_404_when_card_belongs_to_a_different_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();

        $otherBoard = Board::factory()->create();
        $otherList = BoardList::factory()->for($otherBoard)->create();
        $otherCard = Card::factory()->for($otherList, 'list')->create();

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->call('editCard', $otherCard->id)
            ->assertStatus(404);
    }

    public function test_reordering_into_a_list_from_a_different_board_404s(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();
        $card = Card::factory()->for($list, 'list')->create();

        $otherBoard = Board::factory()->create();
        $otherList = BoardList::factory()->for($otherBoard)->create();

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->call('reorderCards', $card->id, $otherList->id, [$card->id])
            ->assertStatus(404);
    }
}
