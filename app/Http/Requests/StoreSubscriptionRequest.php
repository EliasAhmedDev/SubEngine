<?php

/**
 * Validation for creating a subscription.
 * Ensures required subscription fields are present.
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_slug' => [
                'required',
                'string',
                'max:120',
                Rule::exists('plans', 'slug')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'auto_renew' => ['required', 'boolean'],
        ];
    }
}
