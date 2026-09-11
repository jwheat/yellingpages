<?php

namespace App\Http\Controllers;

use App\Models\Rant;
use App\Support\RantPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $latest = Rant::query()
            ->approved()
            ->with('author')
            ->withCount(['comments', 'bookmarks', 'reactions'])
            ->latest('published_at')
            ->limit(6)
            ->get()
            ->map(fn (Rant $rant) => RantPresenter::card($rant));

        return Inertia::render('welcome', [
            'latest' => $latest,
            'stats' => [
                'rants' => Rant::approved()->count(),
                'comments' => \App\Models\Comment::count(),
            ],
        ]);
    }
}
