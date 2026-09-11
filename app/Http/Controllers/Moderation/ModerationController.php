<?php

namespace App\Http\Controllers\Moderation;

use App\Enums\RantStatus;
use App\Http\Controllers\Controller;
use App\Models\Rant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModerationController extends Controller
{
    public function index(): Response
    {
        $pending = Rant::query()
            ->where('status', RantStatus::Pending)
            ->with('author')
            ->latest()
            ->get()
            ->map(fn (Rant $rant) => $this->moderationView($rant));

        $reported = Rant::query()
            ->whereHas('reports', fn ($q) => $q->where('resolved', false))
            ->withCount(['reports' => fn ($q) => $q->where('resolved', false)])
            ->with('author')
            ->get()
            ->map(fn (Rant $rant) => [
                ...$this->moderationView($rant),
                'reports_count' => $rant->reports_count,
            ]);

        return Inertia::render('moderation/index', [
            'pending' => $pending,
            'reported' => $reported,
        ]);
    }

    public function approve(Request $request, Rant $rant): RedirectResponse
    {
        // Don't publish something with no scrubbed body yet.
        if (blank($rant->scrubbed_body)) {
            return back()->withErrors(['rant' => 'This rant has not been scrubbed yet.']);
        }

        $rant->update([
            'status' => RantStatus::Approved,
            'published_at' => now(),
            'needs_review' => false,
        ]);

        $this->log($request, $rant, 'approved');

        return back()->with('success', 'Rant approved and published.');
    }

    public function reject(Request $request, Rant $rant): RedirectResponse
    {
        $rant->update([
            'status' => RantStatus::Rejected,
            'published_at' => null,
        ]);

        $this->log($request, $rant, 'rejected', $request->string('note')->toString() ?: null);

        return back()->with('success', 'Rant rejected.');
    }

    /**
     * Take down an already-published rant after reports.
     */
    public function remove(Request $request, Rant $rant): RedirectResponse
    {
        $rant->update([
            'status' => RantStatus::Rejected,
            'published_at' => null,
        ]);

        $rant->reports()->update(['resolved' => true]);

        $this->log($request, $rant, 'removed', $request->string('note')->toString() ?: null);

        return back()->with('success', 'Rant removed from the site.');
    }

    /**
     * @return array<string, mixed>
     */
    private function moderationView(Rant $rant): array
    {
        return [
            'id' => $rant->id,
            'title' => $rant->title,
            'slug' => $rant->slug,
            'status' => $rant->status->value,
            'source' => $rant->source->label(),
            'needs_review' => $rant->needs_review,
            // Admin-only: the raw text is shown here so moderators can verify the scrub.
            'original_body' => $rant->original_body,
            'scrubbed_body' => $rant->scrubbed_body,
            'scrub_report' => $rant->scrub_report,
            'author' => $rant->author ? [
                'name' => $rant->author->name,
                'username' => $rant->author->username,
            ] : null,
            'created_human' => $rant->created_at?->diffForHumans(),
        ];
    }

    private function log(Request $request, Rant $rant, string $action, ?string $note = null): void
    {
        $rant->moderationLogs()->create([
            'moderator_id' => $request->user()->id,
            'action' => $action,
            'note' => $note,
        ]);
    }
}
