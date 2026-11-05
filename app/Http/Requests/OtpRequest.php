<?php

namespace App\Http\Requests;

class OtpRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['mobile' => ['required', 'string', 'regex:/^09[0-9]{9}$/']];
    }
}
