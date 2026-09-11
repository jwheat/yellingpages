<?php

use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\ComebackController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Moderation\ModerationController;
use App\Http\Controllers\OgImageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RantController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\RantReportController;
use App\Http\Controllers\Webhooks\InboundEmailController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/rants', [RantController::class, 'index'])->name('rants.index');

// Submitting a rant (auth + verified). Defined before the {slug} route so
// "create" isn't captured as a slug.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/rants/create', [RantController::class, 'create'])->name('rants.create');
    Route::post('/rants', [RantController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('rants.store');
});

Route::get('/rants/{rant:slug}', [RantController::class, 'show'])->name('rants.show');

// Dynamic social share cards (yellow-pages style PNGs, cached).
Route::get('/rants/{rant:slug}/og.png', [OgImageController::class, 'rant'])->name('rants.og');
Route::get('/og.png', [OgImageController::class, 'default'])->name('og.default');

// Social actions (auth required).
Route::middleware('auth')->group(function () {
    Route::post('/rants/{rant:slug}/comments', [CommentController::class, 'store'])
        ->middleware('throttle:20,1')->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    Route::post('/rants/{rant:slug}/reactions', [ReactionController::class, 'store'])
        ->middleware('throttle:30,1')->name('reactions.store');

    Route::post('/rants/{rant:slug}/bookmark', [BookmarkController::class, 'store'])->name('bookmarks.store');
    Route::delete('/rants/{rant:slug}/bookmark', [BookmarkController::class, 'destroy'])->name('bookmarks.destroy');
    Route::get('/bookmarks', [BookmarkController::class, 'index'])->name('bookmarks.index');

    Route::post('/users/{user:username}/follow', [FollowController::class, 'store'])->name('follows.store');
    Route::delete('/users/{user:username}/follow', [FollowController::class, 'destroy'])->name('follows.destroy');

    Route::post('/rants/{rant:slug}/report', [RantReportController::class, 'store'])
        ->middleware('throttle:10,1')->name('rants.report');

    // AI "comeback" generator — costs money when Claude is enabled, so rate-limited.
    Route::post('/rants/{rant:slug}/comeback', [ComebackController::class, 'store'])
        ->middleware('throttle:8,1')->name('rants.comeback');
});

// Moderation queue (admins only).
Route::middleware(['auth', 'can:moderate'])->prefix('moderation')->name('moderation.')->group(function () {
    Route::get('/', [ModerationController::class, 'index'])->name('index');
    Route::post('/{rant:slug}/approve', [ModerationController::class, 'approve'])->name('approve');
    Route::post('/{rant:slug}/reject', [ModerationController::class, 'reject'])->name('reject');
    Route::post('/{rant:slug}/remove', [ModerationController::class, 'remove'])->name('remove');
});

// Inbound email webhook (signed; CSRF-exempt in bootstrap/app.php).
Route::post('/webhooks/inbound-email', InboundEmailController::class)->name('webhooks.inbound-email');

// Authenticated dashboard (from the starter kit).
Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

// Public profile — kept last so the "@username" pattern doesn't shadow others.
Route::get('/@{user:username}', [ProfileController::class, 'show'])->name('profile.show');

require __DIR__.'/settings.php';
