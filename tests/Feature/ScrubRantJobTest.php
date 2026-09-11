<?php

use App\Enums\RantStatus;
use App\Jobs\ScrubRant;
use App\Models\Rant;

it('scrubs the body but leaves the rant pending', function () {
    $rant = Rant::factory()->pending()->create([
        'original_body' => 'My manager Kevin emailed bob@acme.com about it.',
        'scrubbed_body' => null,
    ]);

    (new ScrubRant($rant))->handle(app(\App\Scrubbing\Contracts\RantScrubber::class));

    $rant->refresh();
    expect($rant->scrubbed_body)
        ->not->toBeNull()
        ->not->toContain('bob@acme.com')
        ->not->toContain('Kevin');
    expect($rant->status)->toBe(RantStatus::Pending);
    expect($rant->scrub_report)->toBeArray();
});

it('flags a rant for review when scrubbing changes nothing', function () {
    $rant = Rant::factory()->pending()->create([
        'original_body' => 'nothing here to scrub at all',
        'scrubbed_body' => null,
    ]);

    (new ScrubRant($rant))->handle(app(\App\Scrubbing\Contracts\RantScrubber::class));

    $rant->refresh();
    expect($rant->needs_review)->toBeTrue();
    expect($rant->status)->toBe(RantStatus::Pending);
});
