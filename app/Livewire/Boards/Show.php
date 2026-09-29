<?php

namespace App\Livewire\Boards;

use App\Models\Board;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public Board $board;

    public string $newListName = '';

    public function mount(Board $board): void
    {
        abort_unless($board->user_id === Auth::id(), 404);

        $this->board = $board;

        $this->authorize('view', $this->board);
    }

    public function addList(): void
    {
        $this->authorize('update', $this->board);

        $data = $this->validate([
            'newListName' => ['required', 'string', 'max:255'],
        ], attributes: ['newListName' => 'name']);

        $position = ($this->board->lists()->max('position') ?? -1) + 1;

        $this->board->lists()->create([
            'name' => $data['newListName'],
            'position' => $position,
        ]);

        $this->newListName = '';
        $this->board->unsetRelation('lists');
    }

    public function renameList(int $listId, string $name): void
    {
        $this->authorize('update', $this->board);

        $name = trim($name);

        if ($name === '') {
            return;
        }

        $this->board->lists()->whereKey($listId)->update(['name' => $name]);
        $this->board->unsetRelation('lists');
    }

    public function deleteList(int $listId): void
    {
        $this->authorize('update', $this->board);

        $this->board->lists()->whereKey($listId)->delete();
        $this->board->unsetRelation('lists');
    }

    public function reorderLists(array $orderedIds): void
    {
        $this->authorize('update', $this->board);

        foreach ($orderedIds as $position => $listId) {
            $this->board->lists()->whereKey($listId)->update(['position' => $position]);
        }

        $this->board->unsetRelation('lists');
    }

    public function render()
    {
        return view('livewire.boards.show', [
            'lists' => $this->board->lists()->orderBy('position')->get(),
        ]);
    }
}
