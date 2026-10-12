<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Guarded(['id'])]
class Coop extends Model
{
    public function flock(): BelongsTo
    {
        return $this->belongsTo(Flock::class);
    }

    public function dailyFlockRecord(): HasMany
    {
        return $this->hasMany(DailyFlockRecord::class);
    }
}
