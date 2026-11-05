<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

class RegisterRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['name' => 'required|string|max:60', 'last_name' => 'required|string|max:80', 'mobile' => ['required', 'regex:/^09[0-9]{9}$/', 'unique:users,mobile'], 'password' => ['required', 'string', 'confirmed', 'max:72', function ($attribute, $value, $fail) {
            if (is_string($value) && strlen($value) > 72) {
                $fail('رمز عبور باید حداکثر ۷۲ بایت باشد.');
            }
        }, Password::min(10)->mixedCase()->numbers()], 'role' => 'required|in:worker,employer,seller', 'city' => 'required|string|max:80', 'specialty' => 'required_if:role,worker|nullable|string|max:100', 'business_name' => 'required_if:role,seller|nullable|string|max:120', 'address' => 'required_if:role,seller|nullable|string|max:255', 'terms' => 'accepted'];
    }
}
