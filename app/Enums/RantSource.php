<?php

namespace App\Enums;

enum RantSource: string
{
    case Email = 'email';
    case Web = 'web';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Emailed to rant@yellingpages.com',
            self::Web => 'Submitted on the site',
        };
    }
}
