<?php

use App\Models\Rant;
use Illuminate\Support\Facades\Storage;

it('renders a PNG share card for an approved rant', function () {
    Storage::fake('local');
    $rant = Rant::factory()->approved()->create();

    $response = $this->get("/rants/{$rant->slug}/og.png");

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/png');
    expect(substr($response->getContent(), 0, 8))->toBe("\x89PNG\r\n\x1a\n");
});

it('caches the generated card to storage', function () {
    Storage::fake('local');
    $rant = Rant::factory()->approved()->create();

    $this->get("/rants/{$rant->slug}/og.png")->assertOk();

    $path = "og/rants/{$rant->id}-".substr(sha1($rant->title), 0, 8).'.png';
    Storage::assertExists($path);
});

it('does not expose a share card for a non-approved rant', function () {
    Storage::fake('local');
    $rant = Rant::factory()->pending()->create();

    $this->get("/rants/{$rant->slug}/og.png")->assertNotFound();
});

it('renders a default site share card', function () {
    Storage::fake('local');

    $this->get('/og.png')
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');
});

it('advertises the rant card as og:image on the rant page', function () {
    $rant = Rant::factory()->approved()->create();

    $this->get("/rants/{$rant->slug}")
        ->assertSee('property="og:image"', false)
        ->assertSee("/rants/{$rant->slug}/og.png", false);
});
