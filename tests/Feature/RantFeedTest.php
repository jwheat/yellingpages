<?php

use App\Models\Rant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('shows only approved rants in the feed', function () {
    Rant::factory(3)->approved()->create();
    Rant::factory(2)->pending()->create();

    $this->get('/rants')->assertInertia(fn (Assert $page) => $page
        ->component('rants/index')
        ->has('rants.data', 3)
    );
});

it('returns 404 for a pending rant viewed by the public', function () {
    $rant = Rant::factory()->pending()->create();

    $this->get("/rants/{$rant->slug}")->assertNotFound();
});

it('lets a moderator preview a pending rant', function () {
    $admin = User::factory()->admin()->create();
    $rant = Rant::factory()->pending()->create(['scrubbed_body' => 'preview body']);

    $this->actingAs($admin)
        ->get("/rants/{$rant->slug}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('isPreview', true));
});

it('increments the view count when a rant is shown', function () {
    $rant = Rant::factory()->approved()->create(['view_count' => 0]);

    $this->get("/rants/{$rant->slug}")->assertOk();

    expect($rant->fresh()->view_count)->toBe(1);
});
