<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function makeAttachment(User $owner, array $overrides = []): Attachment
    {
        $board = Board::factory()->for($owner)->create();
        $list = BoardList::factory()->for($board)->create();
        $card = Card::factory()->for($list, 'list')->create();

        $path = "attachments/{$card->id}/".uniqid().'.bin';
        Storage::disk('local')->put($path, 'file contents');

        return Attachment::factory()->for($card)->for($owner)->create(array_merge([
            'path' => $path,
        ], $overrides));
    }

    public function test_owner_can_download_their_attachment(): void
    {
        $user = User::factory()->create();
        $attachment = $this->makeAttachment($user, [
            'filename' => 'report.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $response = $this->actingAs($user)->get(route('attachments.show', $attachment));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_non_image_is_sent_as_an_attachment(): void
    {
        $user = User::factory()->create();
        $attachment = $this->makeAttachment($user, [
            'filename' => 'report.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $response = $this->actingAs($user)->get(route('attachments.show', $attachment));

        $response->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_image_is_sent_inline_by_default(): void
    {
        $user = User::factory()->create();
        $attachment = $this->makeAttachment($user, [
            'filename' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $response = $this->actingAs($user)->get(route('attachments.show', $attachment));

        $response->assertOk();
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    public function test_image_can_be_forced_to_download(): void
    {
        $user = User::factory()->create();
        $attachment = $this->makeAttachment($user, [
            'filename' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $response = $this->actingAs($user)->get(route('attachments.show', $attachment).'?download=1');

        $response->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_another_user_gets_404(): void
    {
        $owner = User::factory()->create();
        $attachment = $this->makeAttachment($owner);

        $intruder = User::factory()->create();

        $response = $this->actingAs($intruder)->get(route('attachments.show', $attachment));

        $response->assertNotFound();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $owner = User::factory()->create();
        $attachment = $this->makeAttachment($owner);

        $response = $this->get(route('attachments.show', $attachment));

        $response->assertRedirect(route('login'));
    }

    public function test_missing_file_on_disk_404s(): void
    {
        $user = User::factory()->create();
        $attachment = $this->makeAttachment($user);

        Storage::disk('local')->delete($attachment->path);

        $response = $this->actingAs($user)->get(route('attachments.show', $attachment));

        $response->assertNotFound();
    }
}
