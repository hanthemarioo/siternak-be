<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Guarded(['id'])]
class Flock extends Model
{
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'closed_at' => 'date',
        ];
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function coops(): HasMany
    {
        return $this->hasMany(Coop::class);
    }

    public function scopeForUser($query, $user)
    {
        $ownerId = $user->role === 'super_admin'
            ? $user->id
            : $user->created_by;

        return $query->whereHas('farm', function ($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        });
    }
}
