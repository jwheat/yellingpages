<?php

namespace App\Scrubbing;

use App\Scrubbing\Contracts\RantScrubber;

/**
 * Offline, dependency-free scrubber. Redacts contact info and swaps detected
 * real names / companies for consistent funny aliases.
 *
 * Order matters: contact info first (so an email's "@" isn't later seen as a
 * handle), then companies (so a company's capitalized words aren't seen as
 * names), then personal names.
 */
class RuleBasedScrubber implements RantScrubber
{
    /** @var array<string, true> */
    private array $firstNames;

    /** @var list<string> */
    private array $nameAliases;

    /** @var list<string> */
    private array $companyAliases;

    /**
     * @param  array{name_aliases?: list<string>, company_aliases?: list<string>}  $config
     */
    public function __construct(array $config = [])
    {
        /** @var list<string> $names */
        $names = require __DIR__.'/data/first_names.php';
        $this->firstNames = array_fill_keys($names, true);

        $this->nameAliases = $config['name_aliases'] ?? ['Karen', 'Chad', 'Brenda', 'Kevin', 'Linda'];
        $this->companyAliases = $config['company_aliases'] ?? ['MegaCorp', 'Initech', 'Globex'];
    }

    public function scrub(string $body): ScrubResult
    {
        $text = $body;

        /** @var list<ScrubChange> $changes */
        $changes = [];

        $text = $this->redact($text, '/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/', '[redacted email]', 'email', $changes);
        $text = $this->redact($text, '~\bhttps?://[^\s<>"\']+~i', '[link removed]', 'url', $changes);
        $text = $this->redact($text, '~\bwww\.[^\s<>"\']+~i', '[link removed]', 'url', $changes);
        $text = $this->redact($text, '/(?<![\w.@])@[A-Za-z0-9_]{2,}/', '@[redacted]', 'handle', $changes);
        $text = $this->redact($text, '/(?<!\d)(?:\+?\d{1,2}[\s.\-]?)?(?:\(?\d{3}\)?[\s.\-]?)\d{3}[\s.\-]?\d{4}(?!\d)/', '[redacted number]', 'phone', $changes);

        $text = $this->swapCompanies($text, $changes);
        $text = $this->swapNames($text, $changes);

        return new ScrubResult($text, $this->mergeChanges($changes), 'rule_based');
    }

    /**
     * @param  list<ScrubChange>  $changes
     */
    private function redact(string $text, string $pattern, string $replacement, string $type, array &$changes): string
    {
        $count = 0;
        $result = preg_replace($pattern, $replacement, $text, -1, $count);

        if ($count > 0) {
            $changes[] = new ScrubChange($type, $replacement, $count);
        }

        return $result ?? $text;
    }

    /**
     * @param  list<ScrubChange>  $changes
     */
    private function swapCompanies(string $text, array &$changes): string
    {
        $suffixes = 'Inc|LLC|L\.L\.C\.|Corp|Corporation|Co|Ltd|Limited|Company|Industries|Solutions|Technologies|Systems|Group|Holdings|Enterprises|Partners|Labs|Software|Consulting';

        $pattern = '/\b([A-Z][\w&.\-]*(?:\s+[A-Z][\w&.\-]*){0,3})\s+('.$suffixes.')\b\.?/';

        $map = [];
        $counts = [];
        $next = 0;

        $result = preg_replace_callback($pattern, function (array $m) use (&$map, &$counts, &$next) {
            $original = $m[0];
            if (! isset($map[$original])) {
                $map[$original] = $this->companyAliases[$next % count($this->companyAliases)]
                    .($next >= count($this->companyAliases) ? ' '.(intdiv($next, count($this->companyAliases)) + 1) : '');
                $next++;
            }
            $alias = $map[$original];
            $counts[$alias] = ($counts[$alias] ?? 0) + 1;

            return $alias;
        }, $text);

        foreach ($counts as $alias => $count) {
            $changes[] = new ScrubChange('place', $alias, $count);
        }

        return $result ?? $text;
    }

    /**
     * @param  list<ScrubChange>  $changes
     */
    private function swapNames(string $text, array &$changes): string
    {
        // A capitalized word (optionally followed by a capitalized surname) is
        // only treated as a name when the first token is a known first name.
        $pattern = '/\b([A-Z][a-z]+)(\s+[A-Z][a-z]+){0,1}\b/';

        $map = [];
        $counts = [];
        $next = 0;

        $result = preg_replace_callback($pattern, function (array $m) use (&$map, &$counts, &$next) {
            $first = strtolower($m[1]);
            if (! isset($this->firstNames[$first])) {
                return $m[0]; // not a recognized name — leave untouched
            }

            $original = $m[0];
            if (! isset($map[$original])) {
                $map[$original] = $this->nameAliases[$next % count($this->nameAliases)]
                    .($next >= count($this->nameAliases) ? ' '.(intdiv($next, count($this->nameAliases)) + 1) : '');
                $next++;
            }
            $alias = $map[$original];
            $counts[$alias] = ($counts[$alias] ?? 0) + 1;

            return $alias;
        }, $text);

        foreach ($counts as $alias => $count) {
            $changes[] = new ScrubChange('name', $alias, $count);
        }

        return $result ?? $text;
    }

    /**
     * Collapse duplicate (type, alias) rows produced across passes.
     *
     * @param  list<ScrubChange>  $changes
     * @return list<ScrubChange>
     */
    private function mergeChanges(array $changes): array
    {
        $merged = [];
        foreach ($changes as $c) {
            $key = $c->type.'|'.$c->alias;
            $merged[$key] = isset($merged[$key])
                ? new ScrubChange($c->type, $c->alias, $merged[$key]->count + $c->count)
                : $c;
        }

        return array_values($merged);
    }
}
