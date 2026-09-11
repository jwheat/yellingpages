<?php

namespace App\Models;

use App\Enums\RantSource;
use App\Enums\RantStatus;
use Database\Factories\RantFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $title
 * @property string $slug
 * @property string $original_body
 * @property string|null $scrubbed_body
 * @property string|null $excerpt
 * @property RantStatus $status
 * @property RantSource $source
 * @property string|null $submitter_email_hash
 * @property array|null $scrub_report
 * @property bool $needs_review
 * @property Carbon|null $terms_accepted_at
 * @property Carbon|null $published_at
 * @property int $view_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
// original_body holds raw, un-scrubbed PII — keep it out of every serialized payload.
#[Hidden(['original_body'])]
class Rant extends Model
{
    /** @use HasFactory<RantFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => RantStatus::class,
            'source' => RantSource::class,
            'scrub_report' => 'array',
            'needs_review' => 'boolean',
            'terms_accepted_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Rant $rant) {
            if (empty($rant->slug)) {
                $rant->slug = $rant->generateUniqueSlug($rant->title ?: 'rant');
            }
        });
    }

    public function generateUniqueSlug(string $source): string
    {
        $base = Str::slug(Str::words($source, 8, ''));
        $base = $base !== '' ? $base : 'rant';
        $slug = $base;
        $i = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ----- Scopes -----

    /** @param Builder<Rant> $query */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', RantStatus::Approved)
            ->whereNotNull('published_at');
    }

    /** @param Builder<Rant> $query */
    public function scopePending(Builder $query): void
    {
        $query->where('status', RantStatus::Pending);
    }

    public function isApproved(): bool
    {
        return $this->status === RantStatus::Approved && $this->published_at !== null;
    }

    // ----- Relationships -----

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<Comment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /** @return HasMany<Bookmark, $this> */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function bookmarkers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'bookmarks')->withTimestamps();
    }

    /** @return HasMany<Reaction, $this> */
    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    /** @return HasMany<RantReport, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(RantReport::class);
    }

    /** @return HasMany<ModerationLog, $this> */
    public function moderationLogs(): HasMany
    {
        return $this->hasMany(ModerationLog::class);
    }
}
