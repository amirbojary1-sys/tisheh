<?php

namespace App\Http\Requests;

class ProfileRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['name' => 'required|string|max:60', 'last_name' => 'required|string|max:80', 'city' => 'required|string|max:80', 'bio' => 'nullable|string|max:2000', 'specialty' => 'sometimes|nullable|string|max:100', 'business_name' => 'sometimes|required|string|max:120', 'address' => 'sometimes|required|string|max:255'];
    }
}
