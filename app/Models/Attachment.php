<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::deleting(function (Attachment $attachment) {
            Storage::disk('local')->delete($attachment->path);
        });
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function humanSize(): string
    {
        if ($this->size < 1024) {
            return "{$this->size} B";
        }

        if ($this->size < 1024 * 1024) {
            return round($this->size / 1024, 1).' KB';
        }

        return round($this->size / (1024 * 1024), 1).' MB';
    }

    /**
     * Remove the on-disk attachment directories for the given card ids.
     *
     * The DB cascade-deletes attachment rows when a card/list/board is
     * deleted, but that happens at the database level and never fires
     * Eloquent's `deleting` event for the child rows — so the files on
     * disk are orphaned unless purged explicitly before the delete.
     *
     * @param  iterable<int>  $cardIds
     */
    public static function purgeFilesForCardIds(iterable $cardIds): void
    {
        $disk = Storage::disk('local');

        foreach ($cardIds as $cardId) {
            $disk->deleteDirectory("attachments/{$cardId}");
        }
    }
}
