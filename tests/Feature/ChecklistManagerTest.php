<?php

namespace Tests\Feature;

use App\Livewire\Boards\ChecklistManager;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChecklistManagerTest extends TestCase
{
    use RefreshDatabase;

    private function makeCard(User $user): Card
    {
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();

        return Card::factory()->for($list, 'list')->create();
    }

    public function test_user_can_create_a_checklist(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);

        Livewire::actingAs($user)
            ->test(ChecklistManager::class, ['card' => $card])
            ->set('newChecklistTitle', 'Launch tasks')
            ->call('addChecklist');

        $this->assertDatabaseHas('checklists', [
            'card_id' => $card->id,
            'title' => 'Launch tasks',
            'position' => 0,
        ]);
    }

    public function test_user_can_rename_a_checklist(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);
        $checklist = Checklist::factory()->for($card)->create(['title' => 'Old title']);

        Livewire::actingAs($user)
            ->test(ChecklistManager::class, ['card' => $card])
            ->call('renameChecklist', $checklist->id, 'New title');

        $this->assertDatabaseHas('checklists', ['id' => $checklist->id, 'title' => 'New title']);
    }

    public function test_user_can_delete_a_checklist_and_its_items_cascade(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);
        $checklist = Checklist::factory()->for($card)->create();
        $item = ChecklistItem::factory()->for($checklist)->create();

        Livewire::actingAs($user)
            ->test(ChecklistManager::class, ['card' => $card])
            ->call('deleteChecklist', $checklist->id);

        $this->assertDatabaseMissing('checklists', ['id' => $checklist->id]);
        $this->assertDatabaseMissing('checklist_items', ['id' => $item->id]);
    }

    public function test_user_can_add_an_item(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);
        $checklist = Checklist::factory()->for($card)->create();

        Livewire::actingAs($user)
            ->test(ChecklistManager::class, ['card' => $card])
            ->set("newItemContent.{$checklist->id}", 'Buy milk')
            ->call('addItem', $checklist->id)
            ->assertSet("newItemContent.{$checklist->id}", '');

        $this->assertDatabaseHas('checklist_items', [
            'checklist_id' => $checklist->id,
            'content' => 'Buy milk',
            'is_done' => false,
            'position' => 0,
        ]);
    }

    public function test_adding_an_item_with_blank_content_is_ignored(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);
        $checklist = Checklist::factory()->for($card)->create();

        Livewire::actingAs($user)
            ->test(ChecklistManager::class, ['card' => $card])
            ->set("newItemContent.{$checklist->id}", '   ')
            ->call('addItem', $checklist->id);

        $this->assertDatabaseCount('checklist_items', 0);
    }

    public function test_items_get_strictly_increasing_positions(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);
        $checklist = Checklist::factory()->for($card)->create();

        $component = Livewire::actingAs($user)->test(ChecklistManager::class, ['card' => $card]);

        foreach (['First', 'Second', 'Third'] as $content) {
            $component->set("newItemContent.{$checklist->id}", $content)
                ->call('addItem', $checklist->id);
        }

        $positions = ChecklistItem::where('checklist_id', $checklist->id)->orderBy('id')->pluck('position')->all();
        $this->assertSame([0, 1, 2], $positions);
    }

    public function test_user_can_toggle_an_item_done(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);
        $checklist = Checklist::factory()->for($card)->create();
        $item = ChecklistItem::factory()->for($checklist)->create(['is_done' => false]);

        $component = Livewire::actingAs($user)->test(ChecklistManager::class, ['card' => $card]);

        $component->call('toggleItem', $item->id);
        $this->assertTrue($item->fresh()->is_done);

        $component->call('toggleItem', $item->id);
        $this->assertFalse($item->fresh()->is_done);
    }

    public function test_user_can_edit_item_content(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);
        $checklist = Checklist::factory()->for($card)->create();
        $item = ChecklistItem::factory()->for($checklist)->create(['content' => 'Old content']);

        Livewire::actingAs($user)
            ->test(ChecklistManager::class, ['card' => $card])
            ->call('updateItemContent', $item->id, 'New content');

        $this->assertDatabaseHas('checklist_items', ['id' => $item->id, 'content' => 'New content']);
    }

    public function test_user_can_delete_an_item(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);
        $checklist = Checklist::factory()->for($card)->create();
        $item = ChecklistItem::factory()->for($checklist)->create();

        Livewire::actingAs($user)
            ->test(ChecklistManager::class, ['card' => $card])
            ->call('deleteItem', $item->id);

        $this->assertDatabaseMissing('checklist_items', ['id' => $item->id]);
    }

    public function test_progress_counts_including_an_empty_checklist(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);
        $checklist = Checklist::factory()->for($card)->create();
        ChecklistItem::factory()->for($checklist)->create(['is_done' => true]);
        ChecklistItem::factory()->for($checklist)->create(['is_done' => true]);
        ChecklistItem::factory()->for($checklist)->create(['is_done' => false]);

        $emptyChecklist = Checklist::factory()->for($card)->create();

        $component = Livewire::actingAs($user)->test(ChecklistManager::class, ['card' => $card]);

        $checklists = $component->viewData('checklists');
        $withItems = $checklists->firstWhere('id', $checklist->id);
        $empty = $checklists->firstWhere('id', $emptyChecklist->id);

        $this->assertSame(3, $withItems->items->count());
        $this->assertSame(2, $withItems->items->where('is_done', true)->count());
        $this->assertSame(0, $empty->items->count());
    }

    public function test_checklist_actions_404_when_checklist_belongs_to_a_different_card(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);

        $otherCard = $this->makeCard(User::factory()->create());
        $otherChecklist = Checklist::factory()->for($otherCard)->create();

        Livewire::actingAs($user)
            ->test(ChecklistManager::class, ['card' => $card])
            ->call('renameChecklist', $otherChecklist->id, 'Hijacked')
            ->assertStatus(404);
    }

    public function test_item_actions_404_when_item_belongs_to_a_different_cards_checklist(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);

        $otherCard = $this->makeCard(User::factory()->create());
        $otherChecklist = Checklist::factory()->for($otherCard)->create();
        $otherItem = ChecklistItem::factory()->for($otherChecklist)->create();

        Livewire::actingAs($user)
            ->test(ChecklistManager::class, ['card' => $card])
            ->call('toggleItem', $otherItem->id)
            ->assertStatus(404);

        $this->assertFalse($otherItem->fresh()->is_done);
    }

    public function test_checklist_manager_404s_for_a_card_on_a_board_owned_by_another_user(): void
    {
        $user = User::factory()->create();
        $otherUsersCard = $this->makeCard(User::factory()->create());

        Livewire::actingAs($user)
            ->test(ChecklistManager::class, ['card' => $otherUsersCard])
            ->assertStatus(404);
    }
}
