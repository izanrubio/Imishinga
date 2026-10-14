<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Card extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function list(): BelongsTo
    {
        return $this->belongsTo(BoardList::class, 'list_id');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'card_label');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * The visual tone for the due date badge, or null when there is no due date.
     */
    public function dueBadgeTone(): ?string
    {
        if (! $this->due_date) {
            return null;
        }

        if ($this->completed_at) {
            return 'completed';
        }

        if ($this->due_date->isToday()) {
            return 'today';
        }

        if ($this->due_date->isPast()) {
            return 'overdue';
        }

        return 'upcoming';
    }
}
