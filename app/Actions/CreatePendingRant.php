<?php

namespace App\Actions;

use App\Enums\RantSource;
use App\Enums\RantStatus;
use App\Jobs\ScrubRant;
use App\Models\Rant;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Single entry point for creating a rant from any source (web form, email
 * webhook, or the offline ingest command). Always lands the rant as PENDING
 * and queues the scrubber. Publication still requires moderator approval.
 */
class CreatePendingRant
{
    public function fromWeb(User $author, string $title, string $body): Rant
    {
        return $this->create([
            'user_id' => $author->id,
            'title' => $title,
            'original_body' => $body,
            'source' => RantSource::Web,
            'terms_accepted_at' => now(),
        ]);
    }

    public function fromEmail(?string $fromEmail, ?string $subject, string $body): Rant
    {
        return $this->create([
            'user_id' => null,
            'title' => $this->titleFromEmail($subject, $body),
            'original_body' => $body,
            'source' => RantSource::Email,
            'submitter_email_hash' => $fromEmail ? hash('sha256', Str::lower(trim($fromEmail))) : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function create(array $attributes): Rant
    {
        $rant = Rant::create([
            'status' => RantStatus::Pending,
            'scrubbed_body' => null,
            'published_at' => null,
            ...$attributes,
        ]);

        ScrubRant::dispatch($rant);

        return $rant;
    }

    private function titleFromEmail(?string $subject, string $body): string
    {
        // Strip common reply/forward prefixes from the subject.
        $subject = trim(preg_replace('/^(re|fwd|fw)\s*:\s*/i', '', (string) $subject) ?? '');

        if ($subject !== '') {
            return Str::limit($subject, 120, '');
        }

        // No usable subject — derive a title from the first words of the body.
        return Str::limit(Str::words(strip_tags($body), 10, ''), 120, '') ?: 'An anonymous rant';
    }
}
