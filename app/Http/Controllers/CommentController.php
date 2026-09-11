<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Rant;
use App\Support\CommentTree;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Rant $rant): RedirectResponse
    {
        abort_unless($rant->isApproved(), 404);

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        $depth = 0;
        if (! empty($validated['parent_id'])) {
            $parent = $rant->comments()->findOrFail($validated['parent_id']);
            // Clamp depth so threads can't nest forever.
            $depth = min($parent->depth + 1, CommentTree::MAX_DEPTH);
        }

        $rant->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'body' => $validated['body'],
            'depth' => $depth,
        ]);

        return back();
    }

    public function destroy(Request $request, Comment $comment): RedirectResponse
    {
        // Author or moderator can delete.
        abort_unless(
            $comment->user_id === $request->user()->id || $request->user()->can('moderate'),
            403
        );

        $comment->delete();

        return back();
    }
}
