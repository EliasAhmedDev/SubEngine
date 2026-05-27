<?php

/**
 * Payment model.
 * Tracks payment records and statuses.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'billing_cycle_key',
        'subscription_id',
        'provider',
        'provider_payment_id',
        'status',
        'amount_cents',
        'currency',
        'paid_at',
        'payload',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'paid_at' => 'datetime',
        'payload' => 'array',
        'canceled_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
