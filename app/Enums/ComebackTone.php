<?php

namespace App\Enums;

enum ComebackTone: string
{
    case Savage = 'savage';
    case Petty = 'petty';
    case Corporate = 'corporate';
    case Unhinged = 'unhinged';

    public function label(): string
    {
        return match ($this) {
            self::Savage => '🔪 Savage',
            self::Petty => '💅 Petty',
            self::Corporate => '👔 Corporate',
            self::Unhinged => '🤪 Unhinged',
        };
    }

    /**
     * Style direction handed to the model.
     */
    public function direction(): string
    {
        return match ($this) {
            self::Savage => 'Brutal, scorched-earth, but clever and quotable. Land cutting one-liners.',
            self::Petty => 'Maximum pettiness over the smallest grievances. Passive-aggressive and keeping receipts.',
            self::Corporate => 'Weaponized corporate-speak — polite words, devastating meaning. Heavy "per my last email" energy.',
            self::Unhinged => 'Completely unhinged, chaotic, dramatic, and unfiltered. Burn the bridge and the building.',
        };
    }
}
