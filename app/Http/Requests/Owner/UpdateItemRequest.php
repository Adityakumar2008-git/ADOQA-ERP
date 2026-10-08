<?php

namespace App\Http\Requests\Owner;

use App\Enums\InventoryMode;
use App\Enums\ItemStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sku' => ['nullable', 'string', 'max:64'],
            'barcode' => ['nullable', 'string', 'max:64'],
            'category_id' => ['nullable', 'string'],
            'brand_id' => ['nullable', 'string'],
            'unit_id' => ['required', 'string'],
            'inventory_mode' => ['required', Rule::enum(InventoryMode::class)],
            'cost_price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'sale_price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'mrp' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_stock_threshold' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'status' => ['nullable', Rule::enum(ItemStatus::class)],
        ];
    }
}
