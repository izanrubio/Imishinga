<?php

namespace Tests\Feature;

use App\Livewire\Boards\LabelManager;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Label;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class LabelManagerUiTest extends TestCase
{
    use RefreshDatabase;

    private function openManager(User $user, Board $board): Testable
    {
        return Livewire::actingAs($user)
            ->test(LabelManager::class, ['board' => $board])
            ->call('openModal');
    }

    public function test_usage_counts_are_pluralized(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create(['name' => 'Roadmap']);
        $list = BoardList::factory()->for($board)->create();
        Label::factory()->for($board)->create(['name' => 'A none']);
        $one = Label::factory()->for($board)->create(['name' => 'B one']);
        $two = Label::factory()->for($board)->create(['name' => 'C two']);
        $cards = Card::factory()->count(2)->for($list, 'list')->create();
        $one->cards()->attach($cards[0]);
        $two->cards()->attach($cards->pluck('id'));

        $this->openManager($user, $board)
            ->assertSee('Roadmap · 3 labels')
            ->assertSeeInOrder(['A none', '0 cards', 'B one', '1 card', 'C two', '2 cards']);
    }

    public function test_label_count_in_header_is_singular_for_one_label(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create(['name' => 'Solo']);
        Label::factory()->for($board)->create();

        $this->openManager($user, $board)->assertSee('Solo · 1 label');
    }

    public function test_usage_counts_do_not_add_queries_per_label(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $count = function () use ($user, $board): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->openManager($user, $board);

            return count(DB::getQueryLog());
        };

        Label::factory()->for($board)->create();
        $few = $count();
        Label::factory()->count(6)->for($board)->create();

        $this->assertSame($few, $count());
    }

    public function test_create_flow_through_the_form(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();

        $this->openManager($user, $board)
            ->call('startCreate')
            ->assertSet('creating', true)
            ->assertSee('Save label')
            ->set('name', 'Urgent')
            ->set('color', 'orange')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('creating', false)
            ->assertDispatched('labels-updated')
            ->assertSee('Urgent');

        $this->assertDatabaseHas('labels', ['board_id' => $board->id, 'name' => 'Urgent', 'color' => 'orange']);
    }

    public function test_blank_name_shows_the_design_error_message(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();

        $this->openManager($user, $board)
            ->call('startCreate')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name' => 'required'])
            ->assertSee('Name is required.');
    }

    public function test_edit_flow_through_the_form_and_cancel(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $label = Label::factory()->for($board)->create(['name' => 'Old', 'color' => 'red']);

        $this->openManager($user, $board)
            ->call('startEdit', $label->id)
            ->assertSet('color', 'red')
            ->call('cancelForm')
            ->assertSet('editing', null)
            ->call('startEdit', $label->id)
            ->set('name', 'Renamed')
            ->set('color', 'pink')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Renamed');

        $this->assertDatabaseHas('labels', ['id' => $label->id, 'name' => 'Renamed', 'color' => 'pink']);
    }

    public function test_editing_a_label_with_a_legacy_color_falls_back_to_a_key(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $label = Label::factory()->for($board)->create(['color' => '#0079BF']);

        $this->openManager($user, $board)
            ->call('startEdit', $label->id)
            ->assertSet('color', 'blue');
    }

    public function test_delete_dialog_names_the_label_and_the_cards_that_lose_it(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();
        $label = Label::factory()->for($board)->create(['name' => 'Bug']);
        $label->cards()->attach(Card::factory()->count(3)->for($list, 'list')->create());

        $component = $this->openManager($user, $board)
            ->call('confirmDelete', $label->id)
            ->assertSeeHtml('Delete &ldquo;Bug&rdquo;?')
            ->assertSee('It will be removed from 3 cards.');

        $this->assertDatabaseHas('labels', ['id' => $label->id]);

        $component->call('cancelDelete')
            ->assertSet('confirmingDeleteId', null)
            ->assertDontSee('It will be removed from');

        $component->call('confirmDelete', $label->id)
            ->call('delete', $label->id)
            ->assertSet('confirmingDeleteId', null)
            ->assertDispatched('labels-updated');

        $this->assertDatabaseMissing('labels', ['id' => $label->id]);
        $this->assertDatabaseMissing('card_label', ['label_id' => $label->id]);
    }

    public function test_delete_dialog_is_singular_for_one_card(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();
        $label = Label::factory()->for($board)->create();
        $label->cards()->attach(Card::factory()->for($list, 'list')->create());

        $this->openManager($user, $board)
            ->call('confirmDelete', $label->id)
            ->assertSee('It will be removed from 1 card.');
    }

    public function test_confirming_delete_of_a_foreign_label_404s(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $foreign = Label::factory()->for(Board::factory()->create())->create();

        $this->openManager($user, $board)
            ->call('confirmDelete', $foreign->id)
            ->assertStatus(404);
    }
}
