<?php

use App\Enums\RantSource;
use App\Enums\RantStatus;
use App\Models\Rant;

it('ingests an emailed rant as an anonymous pending rant', function () {
    $response = $this->postJson('/webhooks/inbound-email', [
        'from' => 'angry@example.com',
        'subject' => 'My manager Kevin',
        'body' => 'My manager Kevin is unbearable and his email is kevin@acme.com.',
    ]);

    $response->assertStatus(202);

    $rant = Rant::first();
    expect($rant->source)->toBe(RantSource::Email);
    expect($rant->status)->toBe(RantStatus::Pending);
    expect($rant->user_id)->toBeNull();
    // Submitter email is stored only as a hash.
    expect($rant->submitter_email_hash)->toBe(hash('sha256', 'angry@example.com'));
    expect($rant->original_body)->toContain('kevin@acme.com'); // raw kept private
});

it('ignores empty inbound emails', function () {
    $this->postJson('/webhooks/inbound-email', [
        'from' => 'angry@example.com',
        'body' => '   ',
    ])->assertStatus(422);

    expect(Rant::count())->toBe(0);
});

it('rejects inbound email with a bad signature when a secret is configured', function () {
    config(['services.inbound_email.secret' => 'shh']);

    $this->postJson('/webhooks/inbound-email', [
        'from' => 'angry@example.com',
        'body' => 'Something happened at work today.',
    ], ['X-Webhook-Signature' => 'wrong'])->assertStatus(403);

    expect(Rant::count())->toBe(0);
});

it('accepts inbound email with a valid signature', function () {
    config(['services.inbound_email.secret' => 'shh']);

    $payload = json_encode([
        'from' => 'angry@example.com',
        'body' => 'Something happened at work today with Kevin.',
    ]);
    $signature = hash_hmac('sha256', $payload, 'shh');

    $this->call(
        'POST',
        '/webhooks/inbound-email',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
        ],
        $payload
    )->assertStatus(202);

    expect(Rant::count())->toBe(1);
});
