<?php

namespace App\Http\Requests\Owner;

use App\Enums\CatalogStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:64'],
            'code' => ['required', 'string', 'max:32'],
            'status' => ['nullable', Rule::enum(CatalogStatus::class)],
        ];
    }
}
