<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $rant_id
 * @property int|null $moderator_id
 * @property string $action
 * @property string|null $note
 */
class ModerationLog extends Model
{
    protected $guarded = ['id'];

    /** @return BelongsTo<Rant, $this> */
    public function rant(): BelongsTo
    {
        return $this->belongsTo(Rant::class);
    }

    /** @return BelongsTo<User, $this> */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }
}
