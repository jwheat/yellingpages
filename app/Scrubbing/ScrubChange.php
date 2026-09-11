<?php

namespace App\Scrubbing;

/**
 * A single class of change the scrubber made. Deliberately stores NO raw PII —
 * only the kind of thing changed, the alias it became, and how many times.
 */
final class ScrubChange
{
    public function __construct(
        public readonly string $type,  // email | phone | handle | url | name | place
        public readonly string $alias,
        public readonly int $count,
    ) {}

    /** @return array{type: string, alias: string, count: int} */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'alias' => $this->alias,
            'count' => $this->count,
        ];
    }
}
