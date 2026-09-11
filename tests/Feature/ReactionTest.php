<?php

use App\Enums\ReactionType;
use App\Models\Rant;
use App\Models\User;
use App\Support\RantPresenter;

it('requires login to react', function () {
    $rant = Rant::factory()->approved()->create();

    $this->post("/rants/{$rant->slug}/reactions", ['type' => 'fire'])
        ->assertRedirect('/login');

    expect($rant->reactions()->count())->toBe(0);
});

it('adds a reaction', function () {
    $user = User::factory()->create();
    $rant = Rant::factory()->approved()->create();

    $this->actingAs($user)
        ->post("/rants/{$rant->slug}/reactions", ['type' => 'rage'])
        ->assertRedirect();

    expect($rant->reactions()->count())->toBe(1);
    expect($rant->reactions()->first()->type)->toBe(ReactionType::Rage);
});

it('switches reaction type instead of stacking', function () {
    $user = User::factory()->create();
    $rant = Rant::factory()->approved()->create();

    $this->actingAs($user)->post("/rants/{$rant->slug}/reactions", ['type' => 'rage']);
    $this->actingAs($user)->post("/rants/{$rant->slug}/reactions", ['type' => 'fire']);

    expect($rant->reactions()->count())->toBe(1);
    expect($rant->reactions()->first()->type)->toBe(ReactionType::Fire);
});

it('clears a reaction when the same emoji is tapped again', function () {
    $user = User::factory()->create();
    $rant = Rant::factory()->approved()->create();

    $this->actingAs($user)->post("/rants/{$rant->slug}/reactions", ['type' => 'skull']);
    $this->actingAs($user)->post("/rants/{$rant->slug}/reactions", ['type' => 'skull']);

    expect($rant->reactions()->count())->toBe(0);
});

it('rejects an unknown reaction type', function () {
    $user = User::factory()->create();
    $rant = Rant::factory()->approved()->create();

    $this->actingAs($user)
        ->post("/rants/{$rant->slug}/reactions", ['type' => 'banana'])
        ->assertSessionHasErrors('type');
});

it('cannot react to a non-approved rant', function () {
    $user = User::factory()->create();
    $rant = Rant::factory()->pending()->create();

    $this->actingAs($user)
        ->post("/rants/{$rant->slug}/reactions", ['type' => 'fire'])
        ->assertNotFound();
});

it('summarizes counts and the viewer reaction', function () {
    $rant = Rant::factory()->approved()->create();
    $viewer = User::factory()->create();

    $rant->reactions()->create(['user_id' => $viewer->id, 'type' => ReactionType::Fire]);
    $rant->reactions()->create(['user_id' => User::factory()->create()->id, 'type' => ReactionType::Fire]);
    $rant->reactions()->create(['user_id' => User::factory()->create()->id, 'type' => ReactionType::Rage]);

    $summary = RantPresenter::reactionSummary($rant->fresh(), $viewer);

    expect($summary['total'])->toBe(3);
    expect($summary['counts']['fire'])->toBe(2);
    expect($summary['counts']['rage'])->toBe(1);
    expect($summary['mine'])->toBe('fire');
});
