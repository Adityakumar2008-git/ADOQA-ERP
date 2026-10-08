<?php

namespace App\Http\Requests\Admin;

use App\Enums\BillingCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if (is_string($this->features)) {
            $decoded = json_decode($this->features, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $merge['features'] = $decoded;
            } else {
                $lines = array_values(array_filter(array_map('trim', explode("\n", $this->features))));
                $merge['features'] = $lines;
            }
        }

        if ($this->has('is_active')) {
            $merge['is_active'] = filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'billing_cycle' => ['sometimes', 'required', Rule::enum(BillingCycle::class)],
            'trial_days' => ['nullable', 'integer', 'min:0'],
            'features' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
