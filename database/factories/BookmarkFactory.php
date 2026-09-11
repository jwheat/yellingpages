<?php

namespace Database\Factories;

use App\Models\Bookmark;
use App\Models\Rant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bookmark>
 */
class BookmarkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'rant_id' => Rant::factory(),
        ];
    }
}
