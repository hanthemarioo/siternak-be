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
        if ($user->role === 'super_admin') {
            return $query->whereHas('farm', fn ($q) => $q->where('owner_id', $user->id));
        }

        return $query->where('farm_id', $user->farm_id);
    }
}
