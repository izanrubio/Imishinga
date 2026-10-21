<?php

namespace App\Livewire\Boards;

use App\Enums\LabelColor;
use App\Models\Board;
use App\Models\Label;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class LabelManager extends Component
{
    public Board $board;

    public bool $showModal = false;

    public bool $creating = false;

    public ?int $confirmingDeleteId = null;

    public ?Label $editing = null;

    public string $name = '';

    public string $color = LabelColor::Blue->value;

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
        $this->confirmingDeleteId = null;
        $this->resetForm();
    }

    public function startCreate(): void
    {
        $this->authorize('update', $this->board);

        $this->resetForm();
        $this->creating = true;
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    public function startEdit(int $labelId): void
    {
        $this->authorize('update', $this->board);

        $label = $this->ownedLabel($labelId);

        $this->editing = $label;
        $this->name = $label->name;
        $this->color = LabelColor::fromValueOrDefault($label->color)->value;
    }

    public function save(): void
    {
        $this->authorize('update', $this->board);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', Rule::enum(LabelColor::class)],
        ], [
            'name.required' => 'Name is required.',
            'name.max' => 'Name must be 255 characters or fewer.',
            'color.required' => 'Choose a colour.',
            'color.enum' => 'Choose a colour.',
        ]);

        if ($this->editing) {
            $this->ownedLabel($this->editing->id)->update($data);
        } else {
            $this->board->labels()->create($data);
        }

        $this->dispatch('labels-updated');
        $this->resetForm();
    }

    public function confirmDelete(int $labelId): void
    {
        $this->authorize('update', $this->board);

        $this->confirmingDeleteId = $this->ownedLabel($labelId)->id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(int $labelId): void
    {
        $this->authorize('update', $this->board);

        $this->ownedLabel($labelId)->delete();
        $this->confirmingDeleteId = null;

        $this->dispatch('labels-updated');
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editing = null;
        $this->creating = false;
        $this->name = '';
        $this->color = LabelColor::Blue->value;
        $this->resetValidation();
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
            'labels' => $this->board->labels()->withCount('cards')->orderBy('name')->get(),
        ]);
    }
}
