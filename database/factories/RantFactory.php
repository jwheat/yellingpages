<?php

namespace Database\Factories;

use App\Enums\RantSource;
use App\Enums\RantStatus;
use App\Models\Rant;
use App\Models\User;
use App\Scrubbing\Contracts\RantScrubber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Rant>
 */
class RantFactory extends Factory
{
    /** @var list<string> */
    private static array $titles = [
        'My boss scheduled a 7am "quick sync" again',
        'They replaced the free coffee with a vending machine',
        'HR called my breakdown a "growth opportunity"',
        'We had a meeting that should have been an email about meetings',
        'The new return-to-office policy is a crime against humanity',
        'My manager took credit for my entire project',
        'They gave us pizza instead of the raise they promised',
        'The Slack notifications never. stop. coming.',
        'I got written up for using my earned PTO',
        'The "unlimited vacation" policy is a beautiful lie',
        'My coworker microwaves fish every single day',
        'Corporate rebranded our values into a 40-page PDF',
    ];

    /** @var list<string> */
    private static array $bodies = [
        "So today Karen Johnson decided that the entire team needs to be in the office at 7am for a 'culture sync.' I emailed bob.smith@acme.com to complain and got nothing back. Acme Industries has truly lost the plot. Call me at (555) 123-4567 if you want to commiserate.",
        "Let me tell you about my manager Dave. He took my deck, removed my name, and presented it to the VP at Globex Corporation like it was his own. When I pushed back he told me to be a 'team player.' I'm done. Follow me @burnedout for updates.",
        "The breakroom poster said 'We're a FAMILY here' right above the notice that they're cutting our health benefits. Linda from HR sent the memo at 4:59pm on a Friday. You can read the whole thing at http://internal.megacorp.com/memo. Unreal.",
        "I have been at Initech for three years. Three years! And today Michael Bolton told me my 'compensation philosophy conversation' was being pushed to next quarter. Again. My email is full, my soul is empty, reach me at jane.doe@initech.com I guess.",
        "We got an all-hands invite titled 'Exciting Changes!' which is corporate for layoffs. Greg the CEO talked for 45 minutes about synergy while people quietly updated their resumes. Soulless Industries LLC, everyone.",
    ];

    public function definition(): array
    {
        $body = fake()->randomElement(self::$bodies);

        return [
            'user_id' => User::factory(),
            'title' => fake()->randomElement(self::$titles),
            'original_body' => $body,
            'scrubbed_body' => null,
            'excerpt' => null,
            'status' => RantStatus::Pending,
            'source' => RantSource::Web,
            'submitter_email_hash' => null,
            'scrub_report' => null,
            'needs_review' => false,
            'terms_accepted_at' => now(),
            'published_at' => null,
            'view_count' => fake()->numberBetween(0, 4200),
        ];
    }

    /**
     * Rant emailed in anonymously (no site account).
     */
    public function anonymous(): static
    {
        return $this->state(fn () => [
            'user_id' => null,
            'source' => RantSource::Email,
            'submitter_email_hash' => hash('sha256', fake()->unique()->safeEmail()),
            'terms_accepted_at' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => RantStatus::Pending,
            'scrubbed_body' => null,
            'published_at' => null,
        ]);
    }

    /**
     * Scrubbed and published — the state that shows up in the public feed.
     */
    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            $result = app(RantScrubber::class)->scrub($attributes['original_body']);

            return [
                'status' => RantStatus::Approved,
                'scrubbed_body' => $result->scrubbed,
                'excerpt' => Str::limit(strip_tags($result->scrubbed), 200),
                'scrub_report' => $result->report(),
                'published_at' => fake()->dateTimeBetween('-30 days', 'now'),
            ];
        });
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => RantStatus::Rejected,
            'published_at' => null,
        ]);
    }
}
