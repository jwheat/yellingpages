<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Scrubber Driver
    |--------------------------------------------------------------------------
    |
    | Which driver scrubs inbound rants of names, places and contact info.
    | Supported: "rule_based" (offline, free), "claude" (Anthropic API).
    |
    */

    'default' => env('SCRUBBER_DRIVER', 'rule_based'),

    'rule_based' => [
        // Funny first-name aliases. Detected names are mapped onto these in
        // order, deterministically, and reused within a single rant.
        'name_aliases' => [
            'Karen', 'Chad', 'Brenda', 'Kevin', 'Linda', 'Gary', 'Todd',
            'Janice', 'Brad', 'Sharon', 'Dwight', 'Carol', 'Greg', 'Deborah',
            'Trevor', 'Pam', 'Roger', 'Sandra', 'Doug', 'Cheryl',
        ],

        // Funny stand-ins for companies / organizations / places.
        'company_aliases' => [
            'a certain Fortune 500 company',
            'MegaCorp',
            'Initech',
            'Globex',
            'Soulless Industries LLC',
            'The Company That Shall Not Be Named',
            'Dunder Mifflin',
            'Vandelay Industries',
            'Generic Conglomerate Inc.',
            'a well-known unnamed startup',
        ],
    ],

    'claude' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('SCRUBBER_CLAUDE_MODEL', 'claude-haiku-4-5'),
        'effort' => env('SCRUBBER_CLAUDE_EFFORT', 'low'),
        'max_tokens' => (int) env('SCRUBBER_CLAUDE_MAX_TOKENS', 8192),
    ],

];
