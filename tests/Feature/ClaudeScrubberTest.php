<?php

use App\Scrubbing\ClaudeScrubber;
use App\Scrubbing\RuleBasedScrubber;
use Illuminate\Support\Facades\Http;

function fallbackScrubber(): RuleBasedScrubber
{
    return new RuleBasedScrubber([
        'name_aliases' => ['Karen'],
        'company_aliases' => ['MegaCorp'],
    ]);
}

it('falls back to the rule-based scrubber when no API key is set', function () {
    $scrubber = new ClaudeScrubber(['api_key' => null], fallbackScrubber());

    $result = $scrubber->scrub('My manager Kevin is awful.');

    expect($result->driver)->toContain('fallback');
    expect($result->scrubbed)->not->toContain('Kevin');
});

it('uses Claude output on a successful response', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'stop_reason' => 'end_turn',
            'content' => [[
                'type' => 'text',
                'text' => json_encode([
                    'scrubbed_body' => 'My manager Karen is awful.',
                    'changes' => [['type' => 'name', 'alias' => 'Karen', 'count' => 1]],
                ]),
            ]],
        ]),
    ]);

    $scrubber = new ClaudeScrubber(
        ['api_key' => 'test-key', 'model' => 'claude-haiku-4-5'],
        fallbackScrubber(),
    );

    $result = $scrubber->scrub('My manager Kevin is awful.');

    expect($result->driver)->toBe('claude');
    expect($result->scrubbed)->toBe('My manager Karen is awful.');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.anthropic.com/v1/messages'
            && $request->hasHeader('x-api-key', 'test-key')
            && $request->hasHeader('anthropic-version', '2023-06-01')
            && data_get($request->data(), 'thinking.type') === 'adaptive'
            && data_get($request->data(), 'output_config.format.type') === 'json_schema';
    });
});

it('falls back to rule-based when Claude refuses', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'stop_reason' => 'refusal',
            'content' => [],
        ]),
    ]);

    $scrubber = new ClaudeScrubber(['api_key' => 'test-key'], fallbackScrubber());

    $result = $scrubber->scrub('My manager Kevin is awful.');

    expect($result->driver)->toContain('fallback');
    expect($result->scrubbed)->not->toContain('Kevin');
});
