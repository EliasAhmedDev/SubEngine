<?php

/**
 * Validation for creating a plan.
 * Defines rules for new plan data.
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', 'unique:plans,slug'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price_cents' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'billing_interval' => ['required', 'in:daily,weekly,monthly,quarterly,yearly'],
            'billing_interval_count' => ['required', 'integer', 'min:1', 'max:12'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'is_active' => ['sometimes', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
