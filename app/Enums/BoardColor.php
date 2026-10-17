<?php

namespace App\Enums;

enum BoardColor: string
{
    case Slate = 'slate';
    case Sage = 'sage';
    case Clay = 'clay';
    case Plum = 'plum';
    case Ochre = 'ochre';
    case Teal = 'teal';
    case Rose = 'rose';
    case Graphite = 'graphite';

    /**
     * The complete, literal Tailwind background class for this colour.
     * Kept as a static map so Tailwind's content scanner can see every
     * full class name — never build it from a concatenated string.
     */
    public function bgClass(): string
    {
        return match ($this) {
            self::Slate => 'bg-board-slate',
            self::Sage => 'bg-board-sage',
            self::Clay => 'bg-board-clay',
            self::Plum => 'bg-board-plum',
            self::Ochre => 'bg-board-ochre',
            self::Teal => 'bg-board-teal',
            self::Rose => 'bg-board-rose',
            self::Graphite => 'bg-board-graphite',
        };
    }

    /**
     * Resolve a stored value to a colour, tolerating unknown or missing
     * values by falling back to the default instead of throwing.
     */
    public static function fromValueOrDefault(?string $value): self
    {
        return self::tryFrom($value ?? '') ?? self::Slate;
    }
}
