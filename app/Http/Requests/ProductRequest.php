<?php

namespace App\Http\Requests;

class ProductRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('products.create') ?? false;
    }

    public function rules(): array
    {
        return ['name' => 'required|string|max:160', 'category_id' => 'required|exists:material_categories,id', 'brand' => 'required|string|max:100', 'specifications' => 'required|string|min:20|max:6000', 'price' => 'required|integer|min:1000|max:100000000000', 'unit' => 'required|in:bag,ton,piece,meter,cubic_meter', 'stock' => 'required|integer|between:0,10000000', 'minimum_order' => ['required', 'integer', 'min:1', function ($attribute, $value, $fail) {
            if ((int) $this->input('stock') > 0 && (int) $value > (int) $this->input('stock')) {
                $fail('حداقل سفارش نباید بیشتر از موجودی باشد.');
            }
        }], 'shipping_cost' => 'nullable|integer|min:0|max:100000000000', 'sale_terms' => 'nullable|string|max:3000', 'city' => 'required|string|max:80', 'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096|dimensions:max_width=4096,max_height=4096', 'status' => 'required|in:draft,pending'];
    }
}
