<?php

namespace App\Http\Requests;

class CartCheckoutRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'delivery_address' => 'required|string|min:10|max:2000',
            'note' => 'nullable|string|max:2000',
            'idempotency_key' => 'required|uuid',
        ];
    }

    public function attributes(): array
    {
        return [...parent::attributes(), 'delivery_address' => 'آدرس تحویل', 'idempotency_key' => 'شناسه ثبت سفارش'];
    }
}
