<?php

namespace App\Http\Requests;

use App\Support\IdentityFields;

class IdentityRequest extends BaseRequest
{
    public function rules(): array
    {
        return [...IdentityFields::rules(), 'clear_national_id' => 'sometimes|boolean', 'current_password' => 'required|current_password'];
    }
}
