<?php

use App\Scrubbing\RuleBasedScrubber;

function scrubber(): RuleBasedScrubber
{
    return new RuleBasedScrubber([
        'name_aliases' => ['Karen', 'Chad', 'Brenda'],
        'company_aliases' => ['MegaCorp', 'Initech'],
    ]);
}

it('redacts emails, phone numbers, urls and handles', function () {
    $result = scrubber()->scrub(
        'Reach me at bob.smith@acme.com or (555) 123-4567. See http://acme.com and @bobby.'
    );

    expect($result->scrubbed)
        ->not->toContain('bob.smith@acme.com')
        ->not->toContain('555')
        ->not->toContain('http://acme.com')
        ->not->toContain('@bobby')
        ->toContain('[redacted email]')
        ->toContain('[redacted number]')
        ->toContain('[link removed]');
});

it('swaps a known first name for an alias', function () {
    $result = scrubber()->scrub('My manager Kevin is the worst.');

    expect($result->scrubbed)
        ->not->toContain('Kevin')
        ->toContain('Karen');
});

it('maps the same name to the same alias within one rant', function () {
    $result = scrubber()->scrub('Kevin did it. Later, Kevin did it again.');

    // Both "Kevin" occurrences should become the same alias.
    expect(substr_count($result->scrubbed, 'Karen'))->toBe(2);
});

it('swaps companies that carry a corporate suffix', function () {
    $result = scrubber()->scrub('I work at Globex Corporation, a real place.');

    expect($result->scrubbed)
        ->not->toContain('Globex Corporation')
        ->toContain('MegaCorp');
});

it('reports changes without leaking raw PII', function () {
    $result = scrubber()->scrub('Email bob.smith@acme.com about Kevin.');
    $report = $result->report();

    $json = json_encode($report);
    expect($json)
        ->not->toContain('bob.smith@acme.com')
        ->not->toContain('Kevin');

    expect($result->driver)->toBe('rule_based');
    expect($result->changeCount())->toBeGreaterThan(0);
});
