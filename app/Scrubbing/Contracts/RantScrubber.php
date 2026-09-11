<?php

namespace App\Scrubbing\Contracts;

use App\Scrubbing\ScrubResult;

interface RantScrubber
{
    /**
     * Remove/redact PII and swap real names & places for aliases.
     */
    public function scrub(string $body): ScrubResult;
}
