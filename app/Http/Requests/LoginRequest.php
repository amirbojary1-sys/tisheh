<?php

namespace App\Http\Requests;

class LoginRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['mobile' => ['required', 'regex:/^09[0-9]{9}$/'], 'password' => 'required|string|max:200'];
    }
}
