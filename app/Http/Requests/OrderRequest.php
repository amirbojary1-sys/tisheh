<?php

namespace App\Http\Requests;

class OrderRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['quantity' => 'required|integer|between:1,10000000', 'delivery_address' => 'required|string|min:10|max:2000', 'note' => 'nullable|string|max:2000', 'idempotency_key' => 'required|uuid', 'payment_method' => 'required|in:direct'];
    }

    public function attributes(): array
    {
        return [...parent::attributes(), 'quantity' => 'مقدار سفارش', 'delivery_address' => 'آدرس تحویل', 'payment_method' => 'روش پرداخت'];
    }
}
