<?php

namespace App\Comebacks;

use App\Enums\ComebackTone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Generates the unhinged reply email a ranter wishes they could send. Uses the
 * Anthropic API when a key is configured; otherwise falls back to an offline
 * template generator so the feature always works (and tests stay hermetic).
 *
 * @phpstan-type Comeback array{text: string, source: string}
 */
class ComebackGenerator
{
    /** @return Comeback */
    public function generate(string $rantBody, ComebackTone $tone): array
    {
        if (! empty(config('comeback.api_key'))) {
            try {
                return $this->viaClaude($rantBody, $tone);
            } catch (Throwable $e) {
                Log::warning('ComebackGenerator falling back to offline', ['reason' => $e->getMessage()]);
            }
        }

        return ['text' => $this->offline($rantBody, $tone), 'source' => 'offline'];
    }

    /** @return Comeback */
    private function viaClaude(string $rantBody, ComebackTone $tone): array
    {
        $response = Http::withHeaders([
            'x-api-key' => config('comeback.api_key'),
            'anthropic-version' => '2023-06-01',
        ])
            ->timeout(60)
            ->retry(2, 1000)
            ->acceptJson()
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => config('comeback.model'),
                'max_tokens' => config('comeback.max_tokens'),
                'thinking' => ['type' => 'adaptive'],
                'output_config' => ['effort' => config('comeback.effort')],
                'system' => $this->systemPrompt($tone),
                'messages' => [[
                    'role' => 'user',
                    'content' => "Here is the rant. Write the reply email I wish I could send:\n\n".$rantBody,
                ]],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Anthropic HTTP '.$response->status());
        }

        $data = $response->json();

        if (($data['stop_reason'] ?? null) === 'refusal') {
            throw new \RuntimeException('refused');
        }

        $text = '';
        foreach ($data['content'] ?? [] as $block) {
            if (($block['type'] ?? null) === 'text') {
                $text .= $block['text'];
            }
        }

        $text = trim($text);
        if ($text === '') {
            throw new \RuntimeException('empty');
        }

        return ['text' => $text, 'source' => 'claude'];
    }

    private function systemPrompt(ComebackTone $tone): string
    {
        return implode(' ', [
            'You are the petty, fearless inner voice of a fed-up employee.',
            'Given their workplace rant, write the reply email they wish they could send but never will.',
            'Tone:', $tone->direction(),
            'Keep it under 180 words. Format as a real email: greeting, body, sign-off.',
            'Names in the rant are already anonymized aliases — reuse them as-is.',
            'Be funny and specific to the rant. No disclaimers, no preamble, output only the email.',
        ]);
    }

    /**
     * Offline, template-based comeback. Picks a recipient out of the rant and
     * assembles a tone-appropriate email.
     */
    private function offline(string $rantBody, ComebackTone $tone): string
    {
        $to = $this->recipient($rantBody);

        $templates = match ($tone) {
            ComebackTone::Savage => [
                "Dear {$to},\n\nThank you for the masterclass in how not to manage human beings. Truly, future generations will study you — as a warning.\n\nI'd explain where it all went wrong, but I don't have a whiteboard big enough and you don't have the attention span.\n\nDo not contact me for a reference. Or anything else.\n\nWithout the slightest regard,\nThe one who actually did the work",
                "Dear {$to},\n\nI've reviewed your performance and regret to inform you that it's a no from me, the team, and the laws of basic human decency.\n\nConsider this my notice — to you, personally, that you are the problem.\n\nGood luck explaining the turnover.\n\nColdly,\nEveryone who's left",
            ],
            ComebackTone::Petty => [
                "Hi {$to},\n\nJust circling back (again) to flag that I noticed everything. The 'borrowed' idea. The 4:59pm Friday email. The fun-size candy instead of the raise.\n\nI've kept a folder. It's color-coded. It's beautiful.\n\nAnyway! No worries if not. 🙂\n\nWarmly (but pointedly),\nMe",
                "Hi {$to},\n\nPer my last email, and the one before that, and the sticky note, and the carrier pigeon: I was right.\n\nNot that anyone's counting. (I'm counting. It's a lot.)\n\nKindest petty regards,\nMe",
            ],
            ComebackTone::Corporate => [
                "Hi {$to},\n\nThanks so much for your continued 'leadership.' Per my last email, the synergy you requested has been deprioritized in favor of my sanity.\n\nGoing forward, let's take my participation offline. Permanently.\n\nLet's circle back never.\n\nBest,\nA valued team member (allegedly)",
                "Hi {$to},\n\nQuick note to align on expectations: mine are now zero, and you've consistently exceeded them.\n\nI'll be moving my deliverables to a company that knows what a deliverable is.\n\nLooping out,\nMe",
            ],
            ComebackTone::Unhinged => [
                "{$to}.\n\nThe 7am syncs end NOW. I have seen things in that breakroom microwave that cannot be unseen and I hold YOU responsible.\n\nI am taking the good stapler. I am taking the plant. The plant and I have discussed it and we are BOTH leaving.\n\nDo not follow us.\n\nFeral and free,\nMe",
                "{$to}.\n\nI quit. Also I'm keeping the company hoodie, the ergonomic chair, and my dignity (which I am repossessing from your office effective immediately).\n\nTell HR I said 'lol.'\n\nUnwell and unbothered,\nMe",
            ],
        };

        return $templates[array_rand($templates)];
    }

    private function recipient(string $rantBody): string
    {
        /** @var list<string> $aliases */
        $aliases = config('scrubber.rule_based.name_aliases', []);

        foreach ($aliases as $alias) {
            if (preg_match('/\b'.preg_quote($alias, '/').'\b/i', $rantBody)) {
                return $alias;
            }
        }

        return 'Management';
    }
}
