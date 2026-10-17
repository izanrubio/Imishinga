<?php

namespace App\Models;

use App\Enums\BoardColor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Board extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lists(): HasMany
    {
        return $this->hasMany(BoardList::class);
    }

    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }

    public function cards(): HasManyThrough
    {
        return $this->hasManyThrough(
            Card::class,
            BoardList::class,
            'board_id', // foreign key on lists referencing boards
            'list_id',  // foreign key on cards referencing lists
        );
    }

    public function colorEnum(): BoardColor
    {
        return BoardColor::fromValueOrDefault($this->background_color);
    }

    public function colorClass(): string
    {
        return $this->colorEnum()->bgClass();
    }
}
