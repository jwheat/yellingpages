<?php

use App\Enums\RantStatus;
use App\Models\Rant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('forbids non-admins from the moderation queue', function () {
    $this->actingAs(User::factory()->create())
        ->get('/moderation')
        ->assertForbidden();
});

it('lets an admin see the pending queue', function () {
    $admin = User::factory()->admin()->create();
    Rant::factory()->pending()->create(['scrubbed_body' => 'scrubbed text']);

    $this->actingAs($admin)
        ->get('/moderation')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('moderation/index')
            ->has('pending', 1)
        );
});

it('publishes a rant on approval and shows it in the feed', function () {
    $admin = User::factory()->admin()->create();
    $rant = Rant::factory()->pending()->create([
        'title' => 'Unique pending headline',
        'scrubbed_body' => 'A safely scrubbed body.',
    ]);

    $this->actingAs($admin)
        ->post("/moderation/{$rant->slug}/approve")
        ->assertRedirect();

    $rant->refresh();
    expect($rant->status)->toBe(RantStatus::Approved);
    expect($rant->published_at)->not->toBeNull();

    // A moderation log entry was written.
    expect($rant->moderationLogs()->where('action', 'approved')->exists())->toBeTrue();

    $this->get('/rants')->assertInertia(fn (Assert $page) => $page
        ->component('rants/index')
        ->has('rants.data', 1)
    );
});

it('will not approve a rant that has not been scrubbed', function () {
    $admin = User::factory()->admin()->create();
    $rant = Rant::factory()->pending()->create(['scrubbed_body' => null]);

    $this->actingAs($admin)
        ->post("/moderation/{$rant->slug}/approve")
        ->assertSessionHasErrors('rant');

    expect($rant->fresh()->status)->toBe(RantStatus::Pending);
});

it('removes a reported rant from the site', function () {
    $admin = User::factory()->admin()->create();
    $rant = Rant::factory()->approved()->create();
    $rant->reports()->create(['reason' => 'defamatory']);

    $this->actingAs($admin)
        ->post("/moderation/{$rant->slug}/remove")
        ->assertRedirect();

    expect($rant->fresh()->status)->toBe(RantStatus::Rejected);
    expect($rant->reports()->where('resolved', true)->count())->toBe(1);
});
