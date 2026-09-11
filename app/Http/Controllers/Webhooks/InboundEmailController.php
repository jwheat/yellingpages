<?php

namespace App\Http\Controllers\Webhooks;

use App\Actions\CreatePendingRant;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Receives inbound email forwarded by a mail provider (Mailgun/Postmark inbound
 * parse). The route is unauthenticated, so a shared-secret signature is REQUIRED.
 */
class InboundEmailController extends Controller
{
    public function __invoke(Request $request, CreatePendingRant $intake): JsonResponse
    {
        if (! $this->signatureValid($request)) {
            abort(403, 'Invalid signature.');
        }

        $data = $request->validate([
            'from' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            // Providers send plaintext as "body-plain" (Mailgun) or "TextBody" (Postmark).
            'body' => ['nullable', 'string'],
            'body-plain' => ['nullable', 'string'],
            'TextBody' => ['nullable', 'string'],
        ]);

        $from = $data['from'] ?? null;
        $body = trim((string) ($data['body'] ?? $data['body-plain'] ?? $data['TextBody'] ?? ''));

        if ($body === '') {
            return response()->json(['status' => 'ignored', 'reason' => 'empty body'], 422);
        }

        // Rate-limit per submitter to curb floods (keyed on hashed email or IP).
        $key = 'inbound-email:'.sha1($from ?? $request->ip());
        if (RateLimiter::tooManyAttempts($key, 10)) {
            abort(429, 'Too many submissions.');
        }
        RateLimiter::hit($key, 3600);

        $rant = $intake->fromEmail($from, $data['subject'] ?? null, $body);

        return response()->json(['status' => 'queued', 'id' => $rant->id], 202);
    }

    private function signatureValid(Request $request): bool
    {
        $secret = config('services.inbound_email.secret');

        // No secret configured → reject in production, allow in local for testing.
        if (empty($secret)) {
            return ! app()->isProduction();
        }

        $provided = (string) $request->header('X-Webhook-Signature', '');
        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return $provided !== '' && hash_equals($expected, $provided);
    }
}
