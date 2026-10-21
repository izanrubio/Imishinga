<?php

namespace Tests\Feature;

use App\Livewire\Boards\Show;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReorderListsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reorder_lists_persists_strictly_increasing_positions(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $lists = collect(range(0, 3))->map(fn (int $i) => BoardList::factory()->for($board)->create(['position' => $i]));

        $newOrder = [$lists[2]->id, $lists[0]->id, $lists[3]->id, $lists[1]->id];

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->call('reorderLists', array_map('strval', $newOrder));

        $this->assertSame(
            $newOrder,
            BoardList::where('board_id', $board->id)->orderBy('position')->pluck('id')->all(),
        );
        $this->assertSame(
            [0, 1, 2, 3],
            BoardList::where('board_id', $board->id)->orderBy('position')->pluck('position')->all(),
        );
    }

    public function test_reorder_lists_ignores_lists_of_other_boards(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $mine = BoardList::factory()->for($board)->create(['position' => 0]);
        $foreign = BoardList::factory()->for(Board::factory()->create())->create(['position' => 7]);

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->call('reorderLists', [$foreign->id, $mine->id]);

        $this->assertSame(7, $foreign->fresh()->position);
    }
}
