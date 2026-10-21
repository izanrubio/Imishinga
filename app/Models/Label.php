<?php

namespace App\Models;

use App\Enums\LabelColor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Label extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function cards(): BelongsToMany
    {
        return $this->belongsToMany(Card::class, 'card_label');
    }

    public function colorEnum(): LabelColor
    {
        return LabelColor::fromValueOrDefault($this->color);
    }

    public function colorClass(): string
    {
        return $this->colorEnum()->bgClass();
    }
}
