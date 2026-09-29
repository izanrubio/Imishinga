<?php

namespace App\Livewire\Boards;

use App\Models\Board;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public const array COLORS = ['#0079BF', '#D29034', '#519839', '#B04632', '#89609E', '#CD5A91', '#4BBF6B', '#838C91'];

    public bool $showFormModal = false;

    public ?Board $editing = null;

    public string $name = '';

    public string $description = '';

    public string $background_color = self::COLORS[0];

    public function mount(): void
    {
        $this->authorize('viewAny', Board::class);
    }

    public function boards()
    {
        return Auth::user()->boards()->latest()->get();
    }

    public function createNew(): void
    {
        $this->authorize('create', Board::class);

        $this->reset(['editing', 'name', 'description', 'background_color']);
        $this->background_color = self::COLORS[0];
        $this->showFormModal = true;
    }

    public function editBoard(Board $board): void
    {
        $this->authorize('update', $board);

        $this->editing = $board;
        $this->name = $board->name;
        $this->description = (string) $board->description;
        $this->background_color = (string) ($board->background_color ?? self::COLORS[0]);
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'background_color' => ['required', 'string', 'max:20'],
        ]);

        if ($this->editing) {
            $this->authorize('update', $this->editing);
            $this->editing->update($data);
        } else {
            $this->authorize('create', Board::class);
            Auth::user()->boards()->create($data);
        }

        $this->showFormModal = false;
        $this->reset(['editing', 'name', 'description', 'background_color']);
    }

    public function deleteBoard(Board $board): void
    {
        $this->authorize('delete', $board);

        $board->delete();
    }

    public function render()
    {
        return view('livewire.boards.index', [
            'boards' => $this->boards(),
        ]);
    }
}
