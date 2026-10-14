<?php

namespace App\Livewire\Boards;

use App\Models\Attachment;
use App\Models\Card;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class AttachmentManager extends Component
{
    use WithFileUploads;

    public const array ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf', 'txt', 'csv', 'docx', 'xlsx', 'pptx', 'zip',
    ];

    public const array ALLOWED_MIME_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'text/plain', 'text/csv',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/zip', 'application/x-zip-compressed',
    ];

    public const int MAX_KILOBYTES = 10240;

    public Card $card;

    /** @var array<int, TemporaryUploadedFile> */
    public array $newFiles = [];

    public function mount(Card $card): void
    {
        abort_unless($card->list->board->user_id === Auth::id(), 404);

        $this->card = $card;
    }

    protected function rules(): array
    {
        return [
            'newFiles' => ['required', 'array'],
            'newFiles.*' => [
                'required',
                'file',
                'max:'.self::MAX_KILOBYTES,
                'mimes:'.implode(',', self::ALLOWED_EXTENSIONS),
                'mimetypes:'.implode(',', self::ALLOWED_MIME_TYPES),
            ],
        ];
    }

    public function updatedNewFiles(): void
    {
        $this->upload();
    }

    public function upload(): void
    {
        $this->validate();

        foreach ($this->newFiles as $file) {
            $path = $file->store("attachments/{$this->card->id}", 'local');

            $this->card->attachments()->create([
                'user_id' => Auth::id(),
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $this->reset('newFiles');
        $this->dispatch('attachments-updated');
    }

    public function deleteAttachment(int $attachmentId): void
    {
        $this->ownedAttachment($attachmentId)->delete();

        $this->dispatch('attachments-updated');
    }

    private function ownedAttachment(int $attachmentId): Attachment
    {
        $attachment = $this->card->attachments()->whereKey($attachmentId)->first();

        abort_if(! $attachment, 404);

        return $attachment;
    }

    public function render()
    {
        return view('livewire.boards.attachment-manager', [
            'attachments' => $this->card->attachments()->with('user')->latest()->get(),
        ]);
    }
}
