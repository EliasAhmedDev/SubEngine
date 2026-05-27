<?php

/**
 * API resource for subscriptions.
 * Formats subscription models for API consumers.
 */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'auto_renew' => (bool) $this->auto_renew,
            'billing_mode' => $this->auto_renew ? 'automatic' : 'manual',
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'next_billing_at' => $this->next_billing_at,
            'renewal_due_at' => $this->renewal_due_at,
            'pending_expires_at' => $this->pending_expires_at,
            'renewal_failed_at' => $this->renewal_failed_at,
            'expired_at' => $this->expired_at,
            'plan' => $this->whenLoaded('plan', fn () => [
                'id' => $this->plan->id,
                'name' => $this->plan->name,
                'slug' => $this->plan->slug,
                'description' => $this->plan->description,
                'price_cents' => $this->plan->price_cents,
                'currency' => $this->plan->currency,
                'billing_interval' => $this->plan->billing_interval,
                'billing_interval_count' => $this->plan->billing_interval_count,
                'is_active' => $this->plan->is_active,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
