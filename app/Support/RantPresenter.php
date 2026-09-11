<?php

namespace App\Support;

use App\Enums\ReactionType;
use App\Models\Rant;
use App\Models\User;

/**
 * Shapes Rant models into the arrays our Inertia pages consume. Crucially this
 * NEVER emits original_body — only the scrubbed, public-safe fields.
 */
class RantPresenter
{
    /**
     * Compact shape for feed/profile cards.
     *
     * @param  array<int, true>  $bookmarkedIds  keyed by rant id
     * @return array<string, mixed>
     */
    public static function card(Rant $rant, array $bookmarkedIds = []): array
    {
        return [
            'id' => $rant->id,
            'slug' => $rant->slug,
            'title' => $rant->title,
            'excerpt' => $rant->excerpt ?? \Illuminate\Support\Str::limit(strip_tags((string) $rant->scrubbed_body), 200),
            'source' => $rant->source->value,
            'view_count' => $rant->view_count,
            'comments_count' => $rant->comments_count ?? $rant->comments()->count(),
            'bookmarks_count' => $rant->bookmarks_count ?? $rant->bookmarks()->count(),
            'reactions_count' => $rant->reactions_count ?? $rant->reactions()->count(),
            'bookmarked' => isset($bookmarkedIds[$rant->id]),
            'published_at' => $rant->published_at?->toIso8601String(),
            'published_human' => $rant->published_at?->diffForHumans(),
            'author' => self::author($rant),
        ];
    }

    /**
     * Full shape for the rant detail page.
     *
     * @return array<string, mixed>
     */
    public static function detail(Rant $rant, bool $bookmarked = false, ?User $viewer = null): array
    {
        return [
            'id' => $rant->id,
            'slug' => $rant->slug,
            'title' => $rant->title,
            'body' => $rant->scrubbed_body,
            'source' => $rant->source->value,
            'source_label' => $rant->source->label(),
            'view_count' => $rant->view_count,
            'bookmarks_count' => $rant->bookmarks()->count(),
            'bookmarked' => $bookmarked,
            'reactions' => self::reactionSummary($rant, $viewer),
            'published_at' => $rant->published_at?->toIso8601String(),
            'published_human' => $rant->published_at?->diffForHumans(),
            'author' => self::author($rant),
        ];
    }

    /**
     * Per-type reaction counts plus the viewer's current pick.
     *
     * @return array{total: int, counts: array<string, int>, mine: string|null}
     */
    public static function reactionSummary(Rant $rant, ?User $viewer = null): array
    {
        $raw = $rant->reactions()
            ->selectRaw('type, count(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type');

        $counts = [];
        foreach (ReactionType::cases() as $type) {
            $counts[$type->value] = (int) ($raw[$type->value] ?? 0);
        }

        $mine = $viewer
            ? $rant->reactions()->where('user_id', $viewer->id)->value('type')
            : null;

        return [
            'total' => array_sum($counts),
            'counts' => $counts,
            // Normalize to the backed string value (cast may return the enum).
            'mine' => $mine instanceof ReactionType ? $mine->value : $mine,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function author(Rant $rant): ?array
    {
        if (! $rant->author) {
            return null; // anonymous email rant
        }

        return [
            'name' => $rant->author->name,
            'username' => $rant->author->username,
            'avatar_url' => $rant->author->avatar_url,
        ];
    }
}
