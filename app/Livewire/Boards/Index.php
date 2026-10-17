<?php

namespace App\Livewire\Boards;

use App\Enums\BoardColor;
use App\Models\Attachment;
use App\Models\Board;
use App\Models\Card;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public bool $showFormModal = false;

    public ?Board $editing = null;

    public string $name = '';

    public string $description = '';

    public string $background_color = '';

    public ?Board $deleting = null;

    public int $deletingListsCount = 0;

    public int $deletingCardsCount = 0;

    public function mount(): void
    {
        $this->authorize('viewAny', Board::class);

        $this->background_color = BoardColor::Slate->value;
    }

    public function boards()
    {
        return Auth::user()->boards()->withCount(['lists', 'cards'])->latest()->get();
    }

    public function createNew(): void
    {
        $this->authorize('create', Board::class);

        $this->reset(['editing', 'name', 'description', 'background_color']);
        $this->background_color = BoardColor::Slate->value;
        $this->showFormModal = true;
    }

    public function editBoard(Board $board): void
    {
        $this->authorize('update', $board);

        $this->editing = $board;
        $this->name = $board->name;
        $this->description = (string) $board->description;
        $this->background_color = $board->colorEnum()->value;
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->reset(['editing', 'name', 'description', 'background_color']);
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'background_color' => ['required', Rule::enum(BoardColor::class)],
        ]);

        if ($this->editing) {
            $this->authorize('update', $this->editing);
            $this->editing->update($data);
        } else {
            $this->authorize('create', Board::class);
            Auth::user()->boards()->create($data);
        }

        $this->closeFormModal();
    }

    public function confirmDelete(Board $board, int $listsCount, int $cardsCount): void
    {
        $this->authorize('delete', $board);

        $this->deleting = $board;
        $this->deletingListsCount = $listsCount;
        $this->deletingCardsCount = $cardsCount;
    }

    public function cancelDelete(): void
    {
        $this->deleting = null;
    }

    public function deleteBoard(Board $board): void
    {
        $this->authorize('delete', $board);

        $cardIds = Card::whereIn('list_id', $board->lists()->pluck('id'))->pluck('id');
        Attachment::purgeFilesForCardIds($cardIds);

        $board->delete();

        $this->deleting = null;
    }

    public function render()
    {
        return view('livewire.boards.index', [
            'boards' => $this->boards(),
        ]);
    }
}
