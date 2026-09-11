<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Rant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /** @var list<string> */
    private static array $lines = [
        'This is the most relatable thing I have read all week.',
        'I felt this in my soul. Hang in there.',
        'Name and shame, you love to see it.',
        'We had the exact same memo. Are we at the same company??',
        'The fish microwaver is a menace to society.',
        'Sending this to my entire group chat immediately.',
        'HR is not your friend. Never forget it.',
        'Update us when you finally quit, I am invested now.',
        'The 7am sync should be a war crime.',
        'Pizza party energy is just gaslighting with cheese.',
    ];

    public function definition(): array
    {
        return [
            'rant_id' => Rant::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'body' => fake()->randomElement(self::$lines),
            'depth' => 0,
        ];
    }

    public function replyTo(Comment $parent): static
    {
        return $this->state(fn () => [
            'rant_id' => $parent->rant_id,
            'parent_id' => $parent->id,
            'depth' => $parent->depth + 1,
        ]);
    }
}
