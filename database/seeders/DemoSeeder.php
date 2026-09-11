<?php

namespace Database\Seeders;

use App\Enums\ReactionType;
use App\Models\Bookmark;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Rant;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Known admin/moderator account.
        $admin = User::factory()->admin()->create([
            'name' => 'The Moderator',
            'username' => 'admin',
            'email' => 'admin@yellingpages.test',
            'password' => Hash::make('password'),
            'bio' => 'Keeper of the queue. Swapping names so you can yell freely.',
        ]);

        // Known regular account for clicking around.
        $demo = User::factory()->create([
            'name' => 'Demo Ranter',
            'username' => 'demo',
            'email' => 'demo@yellingpages.test',
            'password' => Hash::make('password'),
            'bio' => 'Professionally disgruntled. Allegedly.',
        ]);

        $users = User::factory(12)->create()->push($admin, $demo);

        // Published feed — a mix of authored and anonymous email rants.
        $approved = collect();

        $approved = $approved->merge(
            Rant::factory(14)
                ->approved()
                ->recycle($users)
                ->create()
        );

        $approved = $approved->merge(
            Rant::factory(6)
                ->approved()
                ->anonymous()
                ->create()
        );

        // A few rants waiting in the moderation queue.
        Rant::factory(4)->pending()->recycle($users)->create();
        Rant::factory(2)->pending()->anonymous()->create([
            'scrubbed_body' => null,
        ]);

        // Threaded comments on approved rants.
        $approved->each(function (Rant $rant) use ($users) {
            $top = Comment::factory(rand(1, 4))
                ->recycle($users)
                ->create(['rant_id' => $rant->id]);

            // Some replies one level deep.
            $top->random(min(2, $top->count()))->each(function (Comment $parent) use ($users) {
                Comment::factory(rand(1, 2))
                    ->recycle($users)
                    ->replyTo($parent)
                    ->create(['user_id' => $users->random()->id]);
            });
        });

        // Follows: everyone follows a few others (no self-follows, no dupes).
        $users->each(function (User $user) use ($users) {
            $users->where('id', '!=', $user->id)
                ->random(rand(2, 5))
                ->each(fn (User $other) => Follow::firstOrCreate([
                    'follower_id' => $user->id,
                    'followed_id' => $other->id,
                ]));
        });

        // Bookmarks.
        $users->each(function (User $user) use ($approved) {
            $approved->random(rand(1, 5))->each(fn (Rant $rant) => Bookmark::firstOrCreate([
                'user_id' => $user->id,
                'rant_id' => $rant->id,
            ]));
        });

        // Rage reactions: each user reacts to a handful of rants (one each).
        $types = ReactionType::cases();
        $users->each(function (User $user) use ($approved, $types) {
            $approved->random(rand(3, 8))->each(fn (Rant $rant) => Reaction::firstOrCreate(
                ['user_id' => $user->id, 'rant_id' => $rant->id],
                ['type' => $types[array_rand($types)]],
            ));
        });
    }
}
