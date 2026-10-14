<?php

namespace Tests\Feature;

use App\Livewire\Boards\AttachmentManager;
use App\Models\Attachment;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AttachmentManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function makeCard(User $user): Card
    {
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create();

        return Card::factory()->for($list, 'list')->create();
    }

    public function test_user_can_upload_an_allowed_file(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);

        $file = UploadedFile::fake()->create('photo.jpg', 500, 'image/jpeg');

        Livewire::actingAs($user)
            ->test(AttachmentManager::class, ['card' => $card])
            ->set('newFiles', [$file])
            ->call('upload');

        $attachment = Attachment::where('card_id', $card->id)->first();

        $this->assertNotNull($attachment);
        $this->assertSame('photo.jpg', $attachment->filename);
        $this->assertSame('image/jpeg', $attachment->mime_type);
        $this->assertStringStartsWith("attachments/{$card->id}/", $attachment->path);
        Storage::disk('local')->assertExists($attachment->path);

        // the file must not be stored under its original, user-supplied name
        $this->assertStringNotContainsString('photo.jpg', $attachment->path);
    }

    public function test_user_can_upload_several_files_at_once(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);

        $files = [
            UploadedFile::fake()->create('one.pdf', 200, 'application/pdf'),
            UploadedFile::fake()->create('two.txt', 10, 'text/plain'),
        ];

        Livewire::actingAs($user)
            ->test(AttachmentManager::class, ['card' => $card])
            ->set('newFiles', $files)
            ->call('upload');

        $this->assertDatabaseCount('attachments', 2);
    }

    public function test_disallowed_file_type_is_rejected(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);

        $file = UploadedFile::fake()->create('script.php', 10, 'application/x-php');

        Livewire::actingAs($user)
            ->test(AttachmentManager::class, ['card' => $card])
            ->set('newFiles', [$file])
            ->call('upload')
            ->assertHasErrors(['newFiles.0']);

        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_svg_is_rejected(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);

        $file = UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml');

        Livewire::actingAs($user)
            ->test(AttachmentManager::class, ['card' => $card])
            ->set('newFiles', [$file])
            ->call('upload')
            ->assertHasErrors(['newFiles.0']);

        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_oversized_file_is_rejected(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);

        // 11MB, over the 10MB (10240 KB) limit
        $file = UploadedFile::fake()->create('big.pdf', 11 * 1024, 'application/pdf');

        Livewire::actingAs($user)
            ->test(AttachmentManager::class, ['card' => $card])
            ->set('newFiles', [$file])
            ->call('upload')
            ->assertHasErrors();

        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_deleting_an_attachment_removes_the_file_from_storage(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);

        $file = UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg');

        $component = Livewire::actingAs($user)
            ->test(AttachmentManager::class, ['card' => $card])
            ->set('newFiles', [$file])
            ->call('upload');

        $attachment = Attachment::where('card_id', $card->id)->first();
        Storage::disk('local')->assertExists($attachment->path);

        $component->call('deleteAttachment', $attachment->id);

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
        Storage::disk('local')->assertMissing($attachment->path);
    }

    public function test_attachment_actions_404_when_attachment_belongs_to_a_different_card(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);

        $otherCard = $this->makeCard(User::factory()->create());
        $otherAttachment = Attachment::factory()->for($otherCard)->create();

        Livewire::actingAs($user)
            ->test(AttachmentManager::class, ['card' => $card])
            ->call('deleteAttachment', $otherAttachment->id)
            ->assertStatus(404);
    }

    public function test_attachment_manager_404s_for_a_card_on_a_board_owned_by_another_user(): void
    {
        $user = User::factory()->create();
        $otherUsersCard = $this->makeCard(User::factory()->create());

        Livewire::actingAs($user)
            ->test(AttachmentManager::class, ['card' => $otherUsersCard])
            ->assertStatus(404);
    }
}
