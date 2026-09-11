<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Comeback Generator
    |--------------------------------------------------------------------------
    |
    | Writes the unhinged reply email a ranter wishes they could send. When an
    | Anthropic key is present it uses Claude; otherwise it falls back to a
    | (still funny) offline template generator so the feature always works.
    |
    */

    'api_key' => env('ANTHROPIC_API_KEY'),
    'model' => env('COMEBACK_CLAUDE_MODEL', 'claude-haiku-4-5'),
    'effort' => env('COMEBACK_CLAUDE_EFFORT', 'medium'),
    'max_tokens' => (int) env('COMEBACK_CLAUDE_MAX_TOKENS', 1024),

];
