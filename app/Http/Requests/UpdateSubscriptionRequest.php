<?php

/**
 * Validation for updating a subscription.
 * Rules for updating subscription attributes.
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'auto_renew' => ['required', 'boolean'],
        ];
    }
}
