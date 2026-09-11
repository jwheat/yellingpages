<?php

use App\Models\Comment;
use App\Models\Rant;
use App\Models\User;

it('lets a user comment on an approved rant', function () {
    $user = User::factory()->create();
    $rant = Rant::factory()->approved()->create();

    $this->actingAs($user)
        ->post("/rants/{$rant->slug}/comments", ['body' => 'Same thing happened to me!'])
        ->assertRedirect();

    expect($rant->comments()->count())->toBe(1);
});

it('records reply depth for threaded comments', function () {
    $user = User::factory()->create();
    $rant = Rant::factory()->approved()->create();
    $parent = Comment::factory()->create(['rant_id' => $rant->id, 'depth' => 0]);

    $this->actingAs($user)->post("/rants/{$rant->slug}/comments", [
        'body' => 'A reply',
        'parent_id' => $parent->id,
    ])->assertRedirect();

    expect(Comment::where('parent_id', $parent->id)->first()->depth)->toBe(1);
});

it('toggles a bookmark on and off', function () {
    $user = User::factory()->create();
    $rant = Rant::factory()->approved()->create();

    $this->actingAs($user)->post("/rants/{$rant->slug}/bookmark")->assertRedirect();
    expect($user->bookmarks()->count())->toBe(1);

    $this->actingAs($user)->delete("/rants/{$rant->slug}/bookmark")->assertRedirect();
    expect($user->fresh()->bookmarks()->count())->toBe(0);
});

it('lets a user follow and unfollow another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)->post("/users/{$other->username}/follow")->assertRedirect();
    expect($user->isFollowing($other))->toBeTrue();

    $this->actingAs($user)->delete("/users/{$other->username}/follow")->assertRedirect();
    expect($user->fresh()->isFollowing($other))->toBeFalse();
});

it('does not allow following yourself', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post("/users/{$user->username}/follow")
        ->assertSessionHasErrors('user');

    expect($user->following()->count())->toBe(0);
});
