<?php

namespace App\Http\Controllers;

use App\Actions\CreatePendingRant;
use App\Http\Requests\StoreRantRequest;
use App\Models\Rant;
use App\Support\CommentTree;
use App\Support\RantPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RantController extends Controller
{
    public function index(Request $request): Response
    {
        $sort = $request->string('sort')->toString();

        $query = Rant::query()
            ->approved()
            ->with('author')
            ->withCount(['comments', 'bookmarks', 'reactions']);

        match ($sort) {
            'top' => $query->orderByDesc('view_count'),
            'discussed' => $query->orderByDesc('comments_count'),
            default => $query->latest('published_at'),
        };

        $bookmarkedIds = $this->bookmarkedIds($request);

        $rants = $query->paginate(10)->withQueryString()
            ->through(fn (Rant $rant) => RantPresenter::card($rant, $bookmarkedIds));

        return Inertia::render('rants/index', [
            'rants' => $rants,
            'sort' => $sort ?: 'latest',
        ]);
    }

    public function show(Request $request, Rant $rant): Response
    {
        // Only approved rants are public. Moderators may preview anything.
        if (! $rant->isApproved() && ! $request->user()?->can('moderate')) {
            abort(404);
        }

        $rant->increment('view_count');

        $comments = $rant->comments()
            ->with('author')
            ->orderBy('created_at')
            ->get();

        $bookmarked = (bool) $request->user()?->bookmarks()->where('rant_id', $rant->id)->exists();

        $description = Str::limit(strip_tags((string) $rant->scrubbed_body), 160);

        return Inertia::render('rants/show', [
            'rant' => RantPresenter::detail($rant, $bookmarked, $request->user()),
            'comments' => CommentTree::build($comments),
            'canModerate' => (bool) $request->user()?->can('moderate'),
            'isPreview' => ! $rant->isApproved(),
            // Crawler-safe OG/Twitter tags are emitted from this prop in app.blade.php.
            'meta' => [
                'title' => $rant->title.' — Yelling Pages',
                'description' => $description,
                'url' => route('rants.show', $rant),
                'image' => $rant->isApproved() ? route('rants.og', $rant) : null,
                'type' => 'article',
                'noindex' => ! $rant->isApproved(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('rants/create');
    }

    public function store(StoreRantRequest $request, CreatePendingRant $intake): RedirectResponse
    {
        $intake->fromWeb(
            $request->user(),
            (string) $request->validated('title'),
            (string) $request->validated('body'),
        );

        return redirect()
            ->route('home')
            ->with('success', 'Your rant is in. It will go live once a moderator scrubs and approves it.');
    }

    /**
     * Current user's bookmarked rant ids as a lookup map.
     *
     * @return array<int, true>
     */
    private function bookmarkedIds(Request $request): array
    {
        $user = $request->user();

        if (! $user) {
            return [];
        }

        return array_fill_keys($user->bookmarks()->pluck('rant_id')->all(), true);
    }
}
