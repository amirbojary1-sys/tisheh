<?php

namespace App\Http\Requests;

class CartQuantityRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['quantity' => 'required|integer|between:1,10000000'];
    }

    public function attributes(): array
    {
        return [...parent::attributes(), 'quantity' => 'مقدار سفارش'];
    }
}
