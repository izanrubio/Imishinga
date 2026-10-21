<?php

namespace App\Enums;

enum LabelColor: string
{
    case Red = 'red';
    case Orange = 'orange';
    case Amber = 'amber';
    case Green = 'green';
    case Teal = 'teal';
    case Blue = 'blue';
    case Violet = 'violet';
    case Pink = 'pink';

    /**
     * The complete, literal Tailwind background class for this colour.
     * Kept as a static map so Tailwind's content scanner can see every
     * full class name — never build it from a concatenated string.
     */
    public function bgClass(): string
    {
        return match ($this) {
            self::Red => 'bg-label-red',
            self::Orange => 'bg-label-orange',
            self::Amber => 'bg-label-amber',
            self::Green => 'bg-label-green',
            self::Teal => 'bg-label-teal',
            self::Blue => 'bg-label-blue',
            self::Violet => 'bg-label-violet',
            self::Pink => 'bg-label-pink',
        };
    }

    /**
     * Resolve a stored value to a colour, tolerating unknown or missing
     * values by falling back to the default instead of throwing.
     */
    public static function fromValueOrDefault(?string $value): self
    {
        return self::tryFrom($value ?? '') ?? self::Blue;
    }
}
