<?php

namespace App\Http\Requests;

class ApplicationRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['message' => 'required|string|min:10|max:2000'];
    }
}
