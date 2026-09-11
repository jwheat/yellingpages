<?php

namespace App\Scrubbing;

use Illuminate\Support\Manager;

class ScrubManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('scrubber.default', 'rule_based');
    }

    public function createRuleBasedDriver(): RuleBasedScrubber
    {
        return new RuleBasedScrubber($this->config->get('scrubber.rule_based', []));
    }

    public function createClaudeDriver(): ClaudeScrubber
    {
        return new ClaudeScrubber(
            $this->config->get('scrubber.claude', []),
            $this->createRuleBasedDriver(),
        );
    }
}
