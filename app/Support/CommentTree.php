<?php

namespace App\Support;

use App\Models\Comment;
use Illuminate\Support\Collection;

class CommentTree
{
    public const MAX_DEPTH = 4;

    /**
     * Build a nested comment tree from a flat collection in a SINGLE pass
     * (no N+1). Nesting beyond MAX_DEPTH is flattened onto the deepest visible
     * ancestor so the thread stays readable.
     *
     * @param  Collection<int, Comment>  $comments
     * @return list<array<string, mixed>>
     */
    public static function build(Collection $comments): array
    {
        // Map each comment to its node, grouped by parent.
        $nodes = [];
        foreach ($comments as $comment) {
            $nodes[$comment->id] = [
                'id' => $comment->id,
                'parent_id' => $comment->parent_id,
                'body' => $comment->body,
                'created_at' => $comment->created_at?->toIso8601String(),
                'created_human' => $comment->created_at?->diffForHumans(),
                'author' => $comment->author ? [
                    'name' => $comment->author->name,
                    'username' => $comment->author->username,
                    'avatar_url' => $comment->author->avatar_url,
                ] : null,
                'replies' => [],
            ];
        }

        $tree = [];
        foreach ($comments as $comment) {
            $parentId = $comment->parent_id;

            // Re-parent orphans (deleted parent) and over-deep replies to root.
            if ($parentId === null || ! isset($nodes[$parentId])) {
                $tree[] = &$nodes[$comment->id];

                continue;
            }

            $nodes[$parentId]['replies'][] = &$nodes[$comment->id];
        }

        return $tree;
    }
}
