<?php

namespace Tests\Feature;

use App\Livewire\Boards\LabelManager;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Label;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LabelManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_label(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(LabelManager::class, ['board' => $board])
            ->set('name', 'Urgent')
            ->set('color', '#B04632')
            ->call('save');

        $this->assertDatabaseHas('labels', [
            'board_id' => $board->id,
            'name' => 'Urgent',
            'color' => '#B04632',
        ]);
    }

    public function test_user_can_rename_and_recolor_a_label(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $label = Label::factory()->for($board)->create(['name' => 'Old', 'color' => '#000000']);

        Livewire::actingAs($user)
            ->test(LabelManager::class, ['board' => $board])
            ->call('startEdit', $label->id)
            ->assertSet('name', 'Old')
            ->set('name', 'New name')
            ->set('color', '#519839')
            ->call('save');

        $this->assertDatabaseHas('labels', [
            'id' => $label->id,
            'name' => 'New name',
            'color' => '#519839',
        ]);
    }

    public function test_user_can_delete_a_label_and_it_detaches_from_cards(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $label = Label::factory()->for($board)->create();
        $list = BoardList::factory()->for($board)->create();
        $card = Card::factory()->for($list, 'list')->create();
        $card->labels()->attach($label);

        Livewire::actingAs($user)
            ->test(LabelManager::class, ['board' => $board])
            ->call('delete', $label->id);

        $this->assertDatabaseMissing('labels', ['id' => $label->id]);
        $this->assertDatabaseMissing('card_label', ['label_id' => $label->id]);
    }

    public function test_label_actions_404_when_label_belongs_to_a_different_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();

        $otherBoard = Board::factory()->create();
        $otherLabel = Label::factory()->for($otherBoard)->create();

        Livewire::actingAs($user)
            ->test(LabelManager::class, ['board' => $board])
            ->call('startEdit', $otherLabel->id)
            ->assertStatus(404);
    }

    public function test_label_manager_404s_for_a_board_owned_by_another_user(): void
    {
        $user = User::factory()->create();
        $otherUsersBoard = Board::factory()->create();

        Livewire::actingAs($user)
            ->test(LabelManager::class, ['board' => $otherUsersBoard])
            ->assertStatus(404);
    }
}
