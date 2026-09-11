<?php

namespace App\Http\Controllers;

use App\Models\Rant;
use App\Support\RantPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookmarkController extends Controller
{
    public function index(Request $request): Response
    {
        $rants = $request->user()
            ->bookmarkedRants()
            ->approved()
            ->with('author')
            ->withCount(['comments', 'bookmarks', 'reactions'])
            ->orderByDesc('bookmarks.created_at')
            ->paginate(10)
            ->through(fn (Rant $rant) => RantPresenter::card($rant, [$rant->id => true]));

        return Inertia::render('bookmarks/index', [
            'rants' => $rants,
        ]);
    }

    public function store(Request $request, Rant $rant): RedirectResponse
    {
        $request->user()->bookmarks()->firstOrCreate(['rant_id' => $rant->id]);

        return back();
    }

    public function destroy(Request $request, Rant $rant): RedirectResponse
    {
        $request->user()->bookmarks()->where('rant_id', $rant->id)->delete();

        return back();
    }
}
