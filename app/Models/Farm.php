<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'type', 'location', 'owner_id'])]
class Farm extends Model
{
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function flocks(): HasMany
    {
        return $this->hasMany(Flock::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeForUser($query, $user)
    {
        if ($user->role === 'super_admin') {
            return $query->where('owner_id', $user->id);
        }

        return $query->where('id', $user->farm_id);
    }

    // public function feedInventory(): HasMany
    // {
    //     return $this->hasMany(FeedInventory::class);
    // }

    // public function financialTransactions(): HasMany
    // {
    //     return $this->hasMany(FinancialTransaction::class);
    // }
}
