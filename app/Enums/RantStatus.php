<?php

namespace App\Enums;

enum RantStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting moderation',
            self::Approved => 'Published',
            self::Rejected => 'Rejected',
        };
    }
}
