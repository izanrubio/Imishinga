<?php

namespace Tests\Feature;

use App\Livewire\Boards\AttachmentManager;
use App\Livewire\Boards\ChecklistManager;
use App\Livewire\Boards\CommentManager;
use App\Livewire\Boards\Show;
use App\Models\Attachment;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CardModalUiTest extends TestCase
{
    use RefreshDatabase;

    private function makeCard(User $user): Card
    {
        $board = Board::factory()->for($user)->create();
        $list = BoardList::factory()->for($board)->create(['name' => 'Backlog']);

        return Card::factory()->for($list, 'list')->create(['title' => 'Fix login redirect']);
    }

    private function attach(Card $card, User $user, string $name, string $mime): Attachment
    {
        Storage::disk('local')->put("attachments/{$card->id}/{$name}", 'data');

        return $card->attachments()->create([
            'user_id' => $user->id,
            'filename' => $name,
            'path' => "attachments/{$card->id}/{$name}",
            'mime_type' => $mime,
            'size' => 4,
        ]);
    }

    public function test_modal_renders_the_single_column_design_without_manage_labels(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $card->list->board])
            ->call('editCard', $card->id)
            ->assertSee('Mark as complete')
            ->assertSee('Save changes')
            ->assertSee('Delete card')
            ->assertSeeHtml('in list <span class="font-medium text-ink">Backlog</span>')
            ->assertDontSee('Manage labels')
            ->assertDontSeeHtml('grid-areas');
    }

    public function test_empty_title_shows_the_design_message(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);

        Livewire::actingAs($user)
            ->test(Show::class, ['board' => $card->list->board])
            ->call('editCard', $card->id)
            ->set('cardTitle', '')
            ->call('saveCard')
            ->assertHasErrors(['cardTitle' => 'required'])
            ->assertSee('A card needs a title.');
    }

    public function test_card_delete_goes_through_the_confirm_dialog_and_removes_files(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $card = $this->makeCard($user);
        $attachment = $this->attach($card, $user, 'a.pdf', 'application/pdf');

        $component = Livewire::actingAs($user)
            ->test(Show::class, ['board' => $card->list->board])
            ->call('editCard', $card->id)
            ->call('confirmDeleteCard')
            ->assertSet('confirmingDeleteCard', true)
            ->assertSeeHtml('Delete &ldquo;Fix login redirect&rdquo;?')
            ->assertSee('The card, its checklist, comments and attachments will be permanently deleted.');

        $this->assertDatabaseHas('cards', ['id' => $card->id]);

        $component->call('cancelDeleteCard')->assertSet('confirmingDeleteCard', false);
        $this->assertDatabaseHas('cards', ['id' => $card->id]);

        $component->call('confirmDeleteCard')->call('deleteCard', $card->id);

        $this->assertDatabaseMissing('cards', ['id' => $card->id]);
        Storage::disk('local')->assertMissing($attachment->path);
    }

    public function test_checklist_delete_goes_through_the_confirm_dialog(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);
        $checklist = Checklist::factory()->for($card)->create(['title' => 'Fix steps']);
        ChecklistItem::factory()->count(3)->for($checklist)->create();

        $component = Livewire::actingAs($user)
            ->test(ChecklistManager::class, ['card' => $card])
            ->call('confirmDeleteChecklist', $checklist->id)
            ->assertSeeHtml('Delete &ldquo;Fix steps&rdquo;?')
            ->assertSee('This checklist and its 3 items will be permanently deleted.');

        $this->assertDatabaseHas('checklists', ['id' => $checklist->id]);

        $component->call('cancelDeleteChecklist')->assertSet('confirmingDeleteChecklistId', null);
        $component->call('confirmDeleteChecklist', $checklist->id)
            ->call('deleteChecklist', $checklist->id)
            ->assertSet('confirmingDeleteChecklistId', null)
            ->assertDispatched('checklists-updated');

        $this->assertDatabaseMissing('checklists', ['id' => $checklist->id]);
    }

    public function test_empty_checklist_shows_zero_of_zero_without_dividing_by_zero(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);
        Checklist::factory()->for($card)->create();

        Livewire::actingAs($user)
            ->test(ChecklistManager::class, ['card' => $card])
            ->assertSee('0/0')
            ->assertSee('No items yet')
            ->assertSeeHtml('style="width: 0%"');
    }

    public function test_image_attachment_renders_a_thumbnail_through_the_authenticated_route(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $card = $this->makeCard($user);
        $image = $this->attach($card, $user, 'shot.png', 'image/png');
        $pdf = $this->attach($card, $user, 'doc.pdf', 'application/pdf');

        $html = Livewire::actingAs($user)
            ->test(AttachmentManager::class, ['card' => $card])
            ->html();

        $route = route('attachments.show', $image);

        $this->assertStringContainsString('<img src="'.$route.'"', $html);
        $this->assertStringContainsString('alt="shot.png"', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('object-cover', $html);
        $this->assertSame(1, substr_count($html, '<img'));
        $this->assertStringNotContainsString('src="'.route('attachments.show', $pdf).'"', $html);
        $this->assertStringContainsString('download=1', $html);
    }

    public function test_uploader_name_is_not_rendered(): void
    {
        Storage::fake('local');
        $uploader = User::factory()->create(['name' => 'Zelda Uploader']);
        $card = $this->makeCard($uploader);
        $this->attach($card, $uploader, 'a.pdf', 'application/pdf');

        Livewire::actingAs($uploader)
            ->test(AttachmentManager::class, ['card' => $card])
            ->assertSee('a.pdf')
            ->assertDontSee('Zelda Uploader');
    }

    public function test_attachment_delete_goes_through_the_dialog_and_removes_the_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $card = $this->makeCard($user);
        $attachment = $this->attach($card, $user, 'a.pdf', 'application/pdf');

        $component = Livewire::actingAs($user)
            ->test(AttachmentManager::class, ['card' => $card])
            ->call('confirmDelete', $attachment->id)
            ->assertSeeHtml('Delete &ldquo;a.pdf&rdquo;?');

        Storage::disk('local')->assertExists($attachment->path);

        $component->call('cancelDelete')->assertSet('confirmingDeleteId', null);
        $component->call('confirmDelete', $attachment->id)
            ->call('deleteAttachment', $attachment->id)
            ->assertSet('confirmingDeleteId', null);

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
        Storage::disk('local')->assertMissing($attachment->path);
    }

    public function test_every_rejected_file_gets_its_own_error_with_the_design_copy(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $card = $this->makeCard($user);

        $html = Livewire::actingAs($user)
            ->test(AttachmentManager::class, ['card' => $card])
            ->set('newFiles', [
                UploadedFile::fake()->create('script.php', 10, 'application/x-php'),
                UploadedFile::fake()->create('tool.exe', 10, 'application/x-msdownload'),
            ])
            ->call('upload')
            ->assertHasErrors(['newFiles.0', 'newFiles.1'])
            ->html();

        $this->assertSame(2, substr_count($html, 'File type not allowed'));
        $this->assertStringContainsString('script.php', $html);
        $this->assertStringContainsString('tool.exe', $html);
        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_comment_dates_use_the_design_format_and_newest_comes_first(): void
    {
        $user = User::factory()->create(['name' => 'Marc Soler']);
        $card = $this->makeCard($user);
        $old = Comment::factory()->for($card)->for($user)->create(['content' => 'Older one', 'created_at' => '2026-10-10 09:12:00']);
        $new = Comment::factory()->for($card)->for($user)->create(['content' => 'Newer one', 'created_at' => '2026-10-11 16:40:00']);

        Livewire::actingAs($user)
            ->test(CommentManager::class, ['card' => $card])
            ->assertSeeInOrder(['Newer one', 'Older one'])
            ->assertSee('11 Oct, 16:40')
            ->assertSee('10 Oct, 09:12')
            ->assertSee('Marc Soler');
    }

    public function test_comment_delete_goes_through_the_dialog_and_only_for_the_author(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard($user);
        $mine = Comment::factory()->for($card)->for($user)->create(['content' => 'Mine']);
        $theirs = Comment::factory()->for($card)->for(User::factory())->create(['content' => 'Theirs']);

        $component = Livewire::actingAs($user)
            ->test(CommentManager::class, ['card' => $card])
            ->call('confirmDelete', $mine->id)
            ->assertSee('Delete this comment?');

        $this->assertDatabaseHas('comments', ['id' => $mine->id]);

        $component->call('cancelDelete')->assertSet('confirmingDeleteId', null);
        $component->call('confirmDelete', $mine->id)
            ->call('deleteComment', $mine->id)
            ->assertSet('confirmingDeleteId', null);

        $this->assertDatabaseMissing('comments', ['id' => $mine->id]);

        $component->call('confirmDelete', $theirs->id)->assertStatus(403);
        $this->assertDatabaseHas('comments', ['id' => $theirs->id]);
    }
}
