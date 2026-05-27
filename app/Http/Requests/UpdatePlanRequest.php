<?php

/**
 * Validation for updating a plan.
 * Rules applied when modifying plan data.
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $plan = $this->route('plan');

        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'slug' => [
                'sometimes',
                'string',
                'max:120',
                'alpha_dash',
                Rule::unique('plans', 'slug')->ignore($plan?->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'price_cents' => ['sometimes', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'billing_interval' => ['sometimes', 'in:daily,weekly,monthly,quarterly,yearly'],
            'billing_interval_count' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'is_active' => ['sometimes', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
