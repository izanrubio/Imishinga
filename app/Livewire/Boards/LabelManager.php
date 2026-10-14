<?php

namespace App\Livewire\Boards;

use App\Models\Board;
use App\Models\Label;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LabelManager extends Component
{
    public const array COLORS = ['#0079BF', '#D29034', '#519839', '#B04632', '#89609E', '#CD5A91', '#4BBF6B', '#838C91'];

    public Board $board;

    public bool $showModal = false;

    public ?Label $editing = null;

    public string $name = '';

    public string $color = self::COLORS[0];

    public function mount(Board $board): void
    {
        abort_unless($board->user_id === Auth::id(), 404);

        $this->board = $board;
    }

    public function openModal(): void
    {
        $this->authorize('view', $this->board);

        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function startCreate(): void
    {
        $this->authorize('update', $this->board);

        $this->resetForm();
    }

    public function startEdit(int $labelId): void
    {
        $this->authorize('update', $this->board);

        $label = $this->ownedLabel($labelId);

        $this->editing = $label;
        $this->name = $label->name;
        $this->color = $label->color;
    }

    public function save(): void
    {
        $this->authorize('update', $this->board);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:20'],
        ]);

        if ($this->editing) {
            $this->ownedLabel($this->editing->id)->update($data);
        } else {
            $this->board->labels()->create($data);
        }

        $this->dispatch('labels-updated');
        $this->resetForm();
    }

    public function delete(int $labelId): void
    {
        $this->authorize('update', $this->board);

        $this->ownedLabel($labelId)->delete();

        $this->dispatch('labels-updated');
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editing = null;
        $this->name = '';
        $this->color = self::COLORS[0];
    }

    private function ownedLabel(int $labelId): Label
    {
        $label = $this->board->labels()->whereKey($labelId)->first();

        abort_if(! $label, 404);

        return $label;
    }

    public function render()
    {
        return view('livewire.boards.label-manager', [
            'labels' => $this->board->labels()->orderBy('name')->get(),
        ]);
    }
}
