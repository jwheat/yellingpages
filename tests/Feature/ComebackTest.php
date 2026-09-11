<?php

use App\Models\Rant;
use App\Models\User;
use Illuminate\Support\Facades\Http;

it('requires login to generate a comeback', function () {
    $rant = Rant::factory()->approved()->create();

    $this->postJson("/rants/{$rant->slug}/comeback", ['tone' => 'savage'])
        ->assertUnauthorized();
});

it('generates an offline comeback when no API key is set', function () {
    config(['comeback.api_key' => null]);

    $user = User::factory()->create();
    $rant = Rant::factory()->approved()->create();

    $this->actingAs($user)
        ->postJson("/rants/{$rant->slug}/comeback", ['tone' => 'corporate'])
        ->assertOk()
        ->assertJson(['tone' => 'corporate', 'source' => 'offline'])
        ->assertJsonStructure(['comeback', 'tone', 'source']);
});

it('validates the tone', function () {
    $user = User::factory()->create();
    $rant = Rant::factory()->approved()->create();

    $this->actingAs($user)
        ->postJson("/rants/{$rant->slug}/comeback", ['tone' => 'polite'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('tone');
});

it('cannot generate a comeback for a non-approved rant', function () {
    $user = User::factory()->create();
    $rant = Rant::factory()->pending()->create();

    $this->actingAs($user)
        ->postJson("/rants/{$rant->slug}/comeback", ['tone' => 'savage'])
        ->assertNotFound();
});

it('uses Claude when an API key is configured', function () {
    config(['comeback.api_key' => 'test-key', 'comeback.model' => 'claude-haiku-4-5']);

    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'stop_reason' => 'end_turn',
            'content' => [['type' => 'text', 'text' => 'Dear Karen, no.']],
        ]),
    ]);

    $user = User::factory()->create();
    $rant = Rant::factory()->approved()->create();

    $this->actingAs($user)
        ->postJson("/rants/{$rant->slug}/comeback", ['tone' => 'savage'])
        ->assertOk()
        ->assertJson(['comeback' => 'Dear Karen, no.', 'source' => 'claude']);

    Http::assertSent(fn ($request) => $request->url() === 'https://api.anthropic.com/v1/messages'
        && $request->hasHeader('x-api-key', 'test-key')
        && data_get($request->data(), 'thinking.type') === 'adaptive');
});

it('falls back to offline when Claude fails', function () {
    config(['comeback.api_key' => 'test-key']);

    Http::fake([
        'api.anthropic.com/*' => Http::response([], 500),
    ]);

    $user = User::factory()->create();
    $rant = Rant::factory()->approved()->create();

    $this->actingAs($user)
        ->postJson("/rants/{$rant->slug}/comeback", ['tone' => 'unhinged'])
        ->assertOk()
        ->assertJson(['source' => 'offline']);
});
