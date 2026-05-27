<?php

/**
 * Subscription model.
 * Represents a user's subscription and its state.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'auto_renew',
        'starts_at',
        'ends_at',
        'next_billing_at',
        'renewal_due_at',
        'pending_expires_at',
        'reminder_3_days_sent_at',
        'reminder_1_day_sent_at',
        'renewal_failed_at',
        'expired_at',
        'stripe_customer_id',
        'stripe_payment_method_id',
        'canceled_at',
        'paused_at',
        'metadata',
    ];

    protected $casts = [
        'auto_renew' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'next_billing_at' => 'datetime',
        'renewal_due_at' => 'datetime',
        'pending_expires_at' => 'datetime',
        'reminder_3_days_sent_at' => 'datetime',
        'reminder_1_day_sent_at' => 'datetime',
        'renewal_failed_at' => 'datetime',
        'expired_at' => 'datetime',
        'canceled_at' => 'datetime',
        'paused_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->where('status', 'active')
                ->orWhere(function (Builder $query) {
                    $query->where('status', 'pending')
                        ->where(function (Builder $query) {
                            $query->whereNull('pending_expires_at')
                                ->orWhere('pending_expires_at', '>', now());
                        });
                });
        });
    }

    public function scopeRenewalDue(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('auto_renew', true)
            ->whereNotNull('renewal_due_at')
            ->where('renewal_due_at', '<=', now());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('auto_renew', false)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now());
    }

    public function scopeStalePending(Builder $query): Builder
    {
        return $query->where('status', 'pending')
            ->whereNotNull('pending_expires_at')
            ->where('pending_expires_at', '<=', now());
    }
}
