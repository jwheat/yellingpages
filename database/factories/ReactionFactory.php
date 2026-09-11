<?php

namespace Database\Factories;

use App\Enums\ReactionType;
use App\Models\Rant;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reaction>
 */
class ReactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'rant_id' => Rant::factory(),
            'type' => fake()->randomElement(ReactionType::cases()),
        ];
    }
}
