<?php

namespace App\Enums;

enum ReactionType: string
{
    case Fire = 'fire';
    case Rage = 'rage';
    case Skull = 'skull';
    case Lol = 'lol';
    case Oof = 'oof';

    public function emoji(): string
    {
        return match ($this) {
            self::Fire => '🔥',
            self::Rage => '😤',
            self::Skull => '💀',
            self::Lol => '😂',
            self::Oof => '🫠',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Fire => 'This is fire',
            self::Rage => 'Pure rage',
            self::Skull => 'I am deceased',
            self::Lol => 'Lol',
            self::Oof => 'Relatable pain',
        };
    }
}
