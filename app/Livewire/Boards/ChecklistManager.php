<?php

namespace App\Livewire\Boards;

use App\Models\Card;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ChecklistManager extends Component
{
    public Card $card;

    public string $newChecklistTitle = 'Checklist';

    /** @var array<int, string> */
    public array $newItemContent = [];

    public ?int $confirmingDeleteChecklistId = null;

    public function mount(Card $card): void
    {
        abort_unless($card->list->board->user_id === Auth::id(), 404);

        $this->card = $card;
    }

    public function addChecklist(): void
    {
        $title = trim($this->newChecklistTitle) !== '' ? trim($this->newChecklistTitle) : 'Checklist';

        $position = ($this->card->checklists()->max('position') ?? -1) + 1;

        $this->card->checklists()->create([
            'title' => $title,
            'position' => $position,
        ]);

        $this->newChecklistTitle = 'Checklist';
        $this->dispatch('checklists-updated');
    }

    public function renameChecklist(int $checklistId, string $title): void
    {
        $title = trim($title);

        if ($title === '') {
            return;
        }

        $this->ownedChecklist($checklistId)->update(['title' => $title]);
        $this->dispatch('checklists-updated');
    }

    public function confirmDeleteChecklist(int $checklistId): void
    {
        $this->confirmingDeleteChecklistId = $this->ownedChecklist($checklistId)->id;
    }

    public function cancelDeleteChecklist(): void
    {
        $this->confirmingDeleteChecklistId = null;
    }

    public function deleteChecklist(int $checklistId): void
    {
        $this->ownedChecklist($checklistId)->delete();
        $this->confirmingDeleteChecklistId = null;
        $this->dispatch('checklists-updated');
    }

    public function addItem(int $checklistId): void
    {
        $checklist = $this->ownedChecklist($checklistId);

        $content = trim($this->newItemContent[$checklistId] ?? '');

        if ($content === '') {
            return;
        }

        $position = ($checklist->items()->max('position') ?? -1) + 1;

        $checklist->items()->create([
            'content' => $content,
            'position' => $position,
        ]);

        $this->newItemContent[$checklistId] = '';
        $this->dispatch('checklists-updated');
    }

    public function toggleItem(int $itemId): void
    {
        $item = $this->ownedItem($itemId);

        $item->update(['is_done' => ! $item->is_done]);

        $this->dispatch('checklists-updated');
    }

    public function updateItemContent(int $itemId, string $content): void
    {
        $content = trim($content);

        if ($content === '') {
            return;
        }

        $this->ownedItem($itemId)->update(['content' => $content]);
        $this->dispatch('checklists-updated');
    }

    public function deleteItem(int $itemId): void
    {
        $this->ownedItem($itemId)->delete();
        $this->dispatch('checklists-updated');
    }

    private function ownedChecklist(int $checklistId): Checklist
    {
        $checklist = $this->card->checklists()->whereKey($checklistId)->first();

        abort_if(! $checklist, 404);

        return $checklist;
    }

    private function ownedItem(int $itemId): ChecklistItem
    {
        $item = ChecklistItem::with('checklist')->find($itemId);

        abort_if(! $item || $item->checklist->card_id !== $this->card->id, 404);

        return $item;
    }

    public function render()
    {
        return view('livewire.boards.checklist-manager', [
            'checklists' => $this->card->checklists()
                ->with(['items' => fn ($query) => $query->orderBy('position')->orderBy('id')])
                ->orderBy('position')
                ->orderBy('id')
                ->get(),
        ]);
    }
}
