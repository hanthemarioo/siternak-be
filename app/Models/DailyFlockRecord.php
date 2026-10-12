<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Guarded(['id'])]
class DailyFlockRecord extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function coop(): BelongsTo
    {
        return $this->belongsTo(Coop::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dailyEggProduction(): HasOne
    {
        return $this->hasOne(DailyEggProduction::class);
    }
}
