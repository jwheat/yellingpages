<?php

namespace App\Scrubbing;

use App\Scrubbing\Contracts\RantScrubber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Claude-powered scrubber. Uses the Anthropic Messages API with a structured
 * JSON-schema output. ALWAYS fails safe to the rule-based scrubber: a failure,
 * refusal, truncation, or empty/unchanged result must never let raw PII through.
 */
class ClaudeScrubber implements RantScrubber
{
    /**
     * @param  array{api_key?: ?string, model?: string, effort?: string, max_tokens?: int}  $config
     */
    public function __construct(
        private readonly array $config,
        private readonly RuleBasedScrubber $fallback,
    ) {}

    public function scrub(string $body): ScrubResult
    {
        if (empty($this->config['api_key'])) {
            return $this->fallback($body, 'claude: missing API key');
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->config['api_key'],
                'anthropic-version' => '2023-06-01',
            ])
                ->timeout(60)
                ->retry(2, 1000)
                ->acceptJson()
                ->post('https://api.anthropic.com/v1/messages', $this->payload($body));
        } catch (Throwable $e) {
            return $this->fallback($body, 'claude: request failed — '.$e->getMessage());
        }

        if ($response->failed()) {
            return $this->fallback($body, 'claude: HTTP '.$response->status());
        }

        $data = $response->json();

        // Check stop_reason BEFORE reading content.
        $stop = $data['stop_reason'] ?? null;
        if (in_array($stop, ['refusal', 'max_tokens'], true)) {
            return $this->fallback($body, 'claude: stop_reason='.$stop);
        }

        $text = $this->firstTextBlock($data);
        if ($text === null) {
            return $this->fallback($body, 'claude: no text block');
        }

        $parsed = json_decode($text, true);
        if (! is_array($parsed) || empty($parsed['scrubbed_body'])) {
            return $this->fallback($body, 'claude: unparseable output');
        }

        // If the model returned content unchanged, treat as suspect and fall back.
        if (trim($parsed['scrubbed_body']) === trim($body)) {
            return $this->fallback($body, 'claude: output unchanged');
        }

        $changes = [];
        foreach ($parsed['changes'] ?? [] as $c) {
            if (isset($c['type'], $c['alias'], $c['count'])) {
                $changes[] = new ScrubChange((string) $c['type'], (string) $c['alias'], (int) $c['count']);
            }
        }

        return new ScrubResult($parsed['scrubbed_body'], $changes, 'claude');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $body): array
    {
        return [
            'model' => $this->config['model'] ?? 'claude-haiku-4-5',
            'max_tokens' => $this->config['max_tokens'] ?? 8192,
            'thinking' => ['type' => 'adaptive'],
            'output_config' => [
                'effort' => $this->config['effort'] ?? 'low',
                'format' => [
                    'type' => 'json_schema',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'scrubbed_body' => ['type' => 'string'],
                            'changes' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'type' => ['type' => 'string', 'enum' => ['email', 'phone', 'handle', 'url', 'name', 'place']],
                                        'alias' => ['type' => 'string'],
                                        'count' => ['type' => 'integer'],
                                    ],
                                    'required' => ['type', 'alias', 'count'],
                                    'additionalProperties' => false,
                                ],
                            ],
                        ],
                        'required' => ['scrubbed_body', 'changes'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'system' => 'You anonymize workplace rant emails for public posting. Replace every real person name, company/organization/place name, email address, phone number, @handle, and URL with funny but consistent aliases (same original -> same alias within the text). Preserve the voice, tone, and meaning. Do not add commentary. Return JSON matching the schema.',
            'messages' => [
                ['role' => 'user', 'content' => $body],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function firstTextBlock(array $data): ?string
    {
        foreach ($data['content'] ?? [] as $block) {
            if (($block['type'] ?? null) === 'text' && isset($block['text'])) {
                return $block['text'];
            }
        }

        return null;
    }

    private function fallback(string $body, string $reason): ScrubResult
    {
        Log::warning('ClaudeScrubber falling back to rule-based', ['reason' => $reason]);

        $result = $this->fallback->scrub($body);

        // Keep the driver label honest about what actually ran.
        return new ScrubResult($result->scrubbed, $result->changes, 'rule_based (claude fallback)');
    }
}
