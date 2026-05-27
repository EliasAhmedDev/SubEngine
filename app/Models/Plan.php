<?php

/**
 * Plan model.
 * Defines subscription plans and attributes.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_cents',
        'currency',
        'billing_interval',
        'billing_interval_count',
        'trial_days',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'billing_interval_count' => 'integer',
        'trial_days' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
