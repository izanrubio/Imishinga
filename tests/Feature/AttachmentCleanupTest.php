<?php

namespace Tests\Feature;

use App\Livewire\Boards\Index as BoardsIndex;
use App\Livewire\Boards\Show;
use App\Models\Attachment;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AttachmentCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function makeAttachment(Card $card, User $user): Attachment
    {
        $path = "attachments/{$card->id}/".uniqid().'.bin';
        Storage::disk('local')->put($path, 'file contents');

        return Attachment::factory()->for($card)->for($user)->create(['path' => $path]);
    }

    public function test_deleting_an_attachment_directly_removes_its_file(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();
        $card = Card::factory()->for($list, 'list')->create();
        $attachment = $this->makeAttachment($card, $user);

        $attachment->delete();

        Storage::disk('local')->assertMissing($attachment->path);
    }

    public function test_deleting_a_card_removes_its_attachment_files(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();
        $card = Card::factory()->for($list, 'list')->create();
        $attachment = $this->makeAttachment($card, $user);

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->call('deleteCard', $card->id);

        $this->assertDatabaseMissing('cards', ['id' => $card->id]);
        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
        Storage::disk('local')->assertMissing($attachment->path);
        Storage::disk('local')->assertDirectoryEmpty("attachments/{$card->id}");
    }

    public function test_deleting_a_list_removes_attachment_files_for_all_its_cards(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();
        $cardA = Card::factory()->for($list, 'list')->create();
        $cardB = Card::factory()->for($list, 'list')->create();
        $attachmentA = $this->makeAttachment($cardA, $user);
        $attachmentB = $this->makeAttachment($cardB, $user);

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $board])
            ->call('deleteList', $list->id);

        $this->assertDatabaseMissing('lists', ['id' => $list->id]);
        Storage::disk('local')->assertMissing($attachmentA->path);
        Storage::disk('local')->assertMissing($attachmentB->path);
    }

    public function test_deleting_a_board_removes_attachment_files_for_every_card_across_every_list(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();
        $listA = BoardList::factory()->for($board)->create();
        $listB = BoardList::factory()->for($board)->create();
        $cardA = Card::factory()->for($listA, 'list')->create();
        $cardB = Card::factory()->for($listB, 'list')->create();
        $attachmentA = $this->makeAttachment($cardA, $user);
        $attachmentB = $this->makeAttachment($cardB, $user);

        Livewire::actingAs($user)
            ->test(BoardsIndex::class)
            ->call('deleteBoard', $board->id);

        $this->assertDatabaseMissing('boards', ['id' => $board->id]);
        Storage::disk('local')->assertMissing($attachmentA->path);
        Storage::disk('local')->assertMissing($attachmentB->path);
    }
}
