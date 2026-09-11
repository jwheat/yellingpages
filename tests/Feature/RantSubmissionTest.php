<?php

use App\Enums\RantSource;
use App\Enums\RantStatus;
use App\Jobs\ScrubRant;
use App\Models\Rant;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('redirects guests away from the submit form', function () {
    $this->get('/rants/create')->assertRedirect('/login');
});

it('lets a verified user submit a rant that lands pending and queues scrubbing', function () {
    Queue::fake();

    $user = User::factory()->create();

    $this->actingAs($user)->post('/rants', [
        'title' => 'My boss is a nightmare',
        'body' => 'My manager Kevin scheduled another 6am standup and I am losing it.',
        'terms' => true,
    ])->assertRedirect('/');

    $rant = Rant::first();
    expect($rant->status)->toBe(RantStatus::Pending);
    expect($rant->source)->toBe(RantSource::Web);
    expect($rant->user_id)->toBe($user->id);
    expect($rant->published_at)->toBeNull();

    Queue::assertPushed(ScrubRant::class);
});

it('rejects submissions when terms are not accepted', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/rants', [
        'title' => 'A valid title',
        'body' => 'This is a sufficiently long rant body to pass validation rules.',
        'terms' => false,
    ])->assertSessionHasErrors('terms');

    expect(Rant::count())->toBe(0);
});

it('blocks bots that fill the honeypot field', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/rants', [
        'title' => 'A valid title',
        'body' => 'This is a sufficiently long rant body to pass validation rules.',
        'terms' => true,
        'website' => 'http://spam.example',
    ])->assertSessionHasErrors('website');

    expect(Rant::count())->toBe(0);
});
