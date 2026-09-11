<?php

namespace App\Scrubbing;

final class ScrubResult
{
    /**
     * @param  list<ScrubChange>  $changes
     */
    public function __construct(
        public readonly string $scrubbed,
        public readonly array $changes,
        public readonly string $driver,
    ) {}

    public function changeCount(): int
    {
        return array_sum(array_map(fn (ScrubChange $c) => $c->count, $this->changes));
    }

    /** @return array{driver: string, changes: list<array{type: string, alias: string, count: int}>} */
    public function report(): array
    {
        return [
            'driver' => $this->driver,
            'changes' => array_map(fn (ScrubChange $c) => $c->toArray(), $this->changes),
        ];
    }
}
