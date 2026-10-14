<?php

namespace App\Livewire\Boards;

use App\Models\Attachment;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Label;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public Board $board;

    public string $newListName = '';

    /** @var array<int, string> */
    public array $newCardTitle = [];

    public bool $showCardModal = false;

    public ?Card $editingCard = null;

    public string $cardTitle = '';

    public string $cardDescription = '';

    public string $cardDueDate = '';

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

        $list = $this->ownedList($listId);

        Attachment::purgeFilesForCardIds($list->cards()->pluck('id'));
        $list->delete();

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

    public function addCard(int $listId): void
    {
        $this->authorize('update', $this->board);

        $list = $this->ownedList($listId);

        $title = trim($this->newCardTitle[$listId] ?? '');

        if ($title === '') {
            return;
        }

        $position = ($list->cards()->max('position') ?? -1) + 1;

        $list->cards()->create([
            'title' => $title,
            'position' => $position,
        ]);

        $this->newCardTitle[$listId] = '';
        $this->board->unsetRelation('lists');
    }

    public function editCard(int $cardId): void
    {
        $this->authorize('update', $this->board);

        $card = $this->ownedCard($cardId);

        $this->editingCard = $card;
        $this->cardTitle = $card->title;
        $this->cardDescription = (string) $card->description;
        $this->cardDueDate = $card->due_date?->format('Y-m-d\TH:i') ?? '';
        $this->showCardModal = true;
    }

    public function saveCard(): void
    {
        $this->authorize('update', $this->board);

        abort_unless($this->editingCard, 404);

        $data = $this->validate([
            'cardTitle' => ['required', 'string', 'max:255'],
            'cardDescription' => ['nullable', 'string', 'max:5000'],
            'cardDueDate' => ['nullable', 'date'],
        ], attributes: ['cardTitle' => 'title', 'cardDescription' => 'description', 'cardDueDate' => 'due date']);

        $this->editingCard->update([
            'title' => $data['cardTitle'],
            'description' => $data['cardDescription'],
            'due_date' => $data['cardDueDate'] !== null && $data['cardDueDate'] !== ''
                ? Carbon::parse($data['cardDueDate'])
                : null,
        ]);

        $this->closeCardModal();
        $this->board->unsetRelation('lists');
    }

    public function clearDueDate(): void
    {
        $this->cardDueDate = '';
    }

    public function deleteCard(int $cardId): void
    {
        $this->authorize('update', $this->board);

        $card = $this->ownedCard($cardId);

        Attachment::purgeFilesForCardIds([$card->id]);
        $card->delete();

        $this->closeCardModal();
        $this->board->unsetRelation('lists');
    }

    public function closeCardModal(): void
    {
        $this->showCardModal = false;
        $this->editingCard = null;
        $this->reset(['cardTitle', 'cardDescription', 'cardDueDate']);
    }

    public function toggleCompleted(int $cardId): void
    {
        $this->authorize('update', $this->board);

        $card = $this->ownedCard($cardId);

        $card->update(['completed_at' => $card->completed_at ? null : now()]);

        if ($this->editingCard?->id === $card->id) {
            $this->editingCard = $card->fresh(['list', 'labels']);
        }

        $this->board->unsetRelation('lists');
    }

    public function toggleCardLabel(int $cardId, int $labelId): void
    {
        $this->authorize('update', $this->board);

        $card = $this->ownedCard($cardId);
        $label = $this->ownedLabel($labelId);

        $card->labels()->toggle($label->id);

        if ($this->editingCard?->id === $card->id) {
            $this->editingCard = $card->fresh(['list', 'labels']);
        }

        $this->board->unsetRelation('lists');
    }

    #[On('labels-updated')]
    public function refreshLabels(): void
    {
        //
    }

    #[On('checklists-updated')]
    public function refreshChecklists(): void
    {
        //
    }

    #[On('comments-updated')]
    public function refreshComments(): void
    {
        //
    }

    #[On('attachments-updated')]
    public function refreshAttachments(): void
    {
        //
    }

    /**
     * Move/reorder a card. $orderedIds is the full, final ordering of card
     * ids within the destination list (including the moved card).
     *
     * @param  array<int, int|string>  $orderedIds
     */
    public function reorderCards(int $cardId, int $toListId, array $orderedIds): void
    {
        $this->authorize('update', $this->board);

        $card = $this->ownedCard($cardId);
        $toList = $this->ownedList($toListId);
        $fromListId = $card->list_id;

        if ($fromListId !== $toList->id) {
            $card->update(['list_id' => $toList->id]);
        }

        foreach ($orderedIds as $position => $id) {
            Card::whereKey($id)->update(['position' => $position]);
        }

        if ($fromListId !== $toList->id) {
            $this->board->lists()->whereKey($fromListId)->first()
                ?->cards()->orderBy('position')->get()
                ->values()
                ->each(fn (Card $remaining, int $position) => $remaining->update(['position' => $position]));
        }

        $this->board->unsetRelation('lists');
    }

    private function ownedList(int $listId): BoardList
    {
        $list = $this->board->lists()->whereKey($listId)->first();

        abort_if(! $list, 404);

        return $list;
    }

    private function ownedCard(int $cardId): Card
    {
        $card = Card::with(['list', 'labels'])->find($cardId);

        abort_if(! $card || $card->list->board_id !== $this->board->id, 404);

        return $card;
    }

    private function ownedLabel(int $labelId): Label
    {
        $label = $this->board->labels()->whereKey($labelId)->first();

        abort_if(! $label, 404);

        return $label;
    }

    public function render()
    {
        return view('livewire.boards.show', [
            'lists' => $this->board->lists()
                ->with(['cards' => fn ($query) => $query->orderBy('position')->orderBy('id')
                    ->with('labels')
                    ->withCount([
                        'checklistItems as checklist_items_count',
                        'checklistItems as checklist_items_done_count' => fn ($q) => $q->where('is_done', true),
                        'comments as comments_count',
                        'attachments as attachments_count',
                    ])])
                ->orderBy('position')
                ->get(),
            'labels' => $this->board->labels()->orderBy('name')->get(),
        ]);
    }
}
