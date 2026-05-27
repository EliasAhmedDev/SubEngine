<?php

/**
 * Webhook event model.
 * Stores incoming webhook payload metadata.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'provider',
        'provider_event_id',
        'event_type',
        'payload',
        'attempts',
        'processed_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
