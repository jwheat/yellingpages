<?php

namespace App\Http\Controllers;

use App\Models\Rant;
use App\Models\User;
use App\Support\RantPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(Request $request, User $user): Response
    {
        $viewer = $request->user();

        $bookmarkedIds = $viewer
            ? array_fill_keys($viewer->bookmarks()->pluck('rant_id')->all(), true)
            : [];

        $rants = $user->rants()
            ->approved()
            ->with('author')
            ->withCount(['comments', 'bookmarks', 'reactions'])
            ->latest('published_at')
            ->paginate(10)
            ->through(fn (Rant $rant) => RantPresenter::card($rant, $bookmarkedIds));

        return Inertia::render('profile/show', [
            'profile' => [
                'name' => $user->name,
                'username' => $user->username,
                'bio' => $user->bio,
                'avatar_url' => $user->avatar_url,
                'joined_human' => $user->created_at?->diffForHumans(),
                'rants_count' => $user->rants()->approved()->count(),
                'followers_count' => $user->followers()->count(),
                'following_count' => $user->following()->count(),
            ],
            'rants' => $rants,
            'isSelf' => $viewer?->id === $user->id,
            'isFollowing' => $viewer ? $viewer->isFollowing($user) : false,
        ]);
    }
}
