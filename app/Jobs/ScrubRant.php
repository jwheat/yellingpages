<?php

namespace App\Jobs;

use App\Models\Rant;
use App\Scrubbing\Contracts\RantScrubber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

class ScrubRant implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public Rant $rant) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(RantScrubber $scrubber): void
    {
        $result = $scrubber->scrub($this->rant->original_body);

        $scrubbed = trim($result->scrubbed);

        // Fail-safe: never persist an empty scrub result.
        if ($scrubbed === '') {
            $this->flagForReview();

            return;
        }

        $this->rant->forceFill([
            'scrubbed_body' => $result->scrubbed,
            'excerpt' => Str::limit(strip_tags($result->scrubbed), 200),
            'scrub_report' => $result->report(),
            // If nothing was changed, a human should eyeball it before approval.
            'needs_review' => $result->changeCount() === 0,
        ])->save();
    }

    /**
     * Last-resort handler after all retries are exhausted. The rant STAYS pending
     * and is flagged — moderation still gates publication, so nothing leaks.
     */
    public function failed(?Throwable $e): void
    {
        $this->flagForReview();
    }

    private function flagForReview(): void
    {
        $this->rant->forceFill(['needs_review' => true])->save();
    }
}
