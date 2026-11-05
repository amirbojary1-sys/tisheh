<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['current_password' => 'required|string|current_password', 'password' => ['required', 'string', 'max:72', function ($attribute, $value, $fail) {
            if (is_string($value) && strlen($value) > 72) {
                $fail('رمز جدید باید حداکثر ۷۲ بایت باشد.');
            }
        }, 'confirmed', 'different:current_password', Password::min(12)->mixedCase()->numbers()]];
    }
}
