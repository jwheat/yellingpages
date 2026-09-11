<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $rant_id
 * @property int|null $user_id
 * @property string $reason
 * @property string|null $details
 * @property bool $resolved
 */
class RantReport extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'resolved' => 'boolean',
        ];
    }

    /** @return BelongsTo<Rant, $this> */
    public function rant(): BelongsTo
    {
        return $this->belongsTo(Rant::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
