<?php

namespace App\Http\Requests;

use App\Support\IdentityFields;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends BaseRequest
{
    public function rules(): array
    {
        return [...IdentityFields::rules(), 'activity_type' => ['sometimes', Rule::in(array_keys(config('network.activities')))], 'additional_roles' => 'sometimes|array|max:3', 'additional_roles.*' => 'required|distinct|in:worker,employer,seller', 'name' => 'required|string|max:60', 'last_name' => 'required|string|max:80', 'mobile' => ['required', 'regex:/^09[0-9]{9}$/', 'unique:users,mobile'], 'password' => ['required', 'string', 'confirmed', 'max:72', function ($attribute, $value, $fail) {
            if (is_string($value) && strlen($value) > 72) {
                $fail('رمز عبور باید حداکثر ۷۲ بایت باشد.');
            }
        }, Password::min(10)->mixedCase()->numbers()], 'role' => 'required|in:worker,employer,seller', 'city' => 'required|string|max:80', 'specialty' => [Rule::requiredIf(fn () => $this->input('role') === 'worker' || (is_array($this->input('additional_roles')) && in_array('worker', $this->input('additional_roles')))), 'nullable', 'string', 'max:100'], 'business_name' => [Rule::requiredIf(fn () => $this->input('role') === 'seller' || (is_array($this->input('additional_roles')) && in_array('seller', $this->input('additional_roles')))), 'nullable', 'string', 'max:120'], 'address' => [Rule::requiredIf(fn () => $this->input('role') === 'seller' || (is_array($this->input('additional_roles')) && in_array('seller', $this->input('additional_roles')))), 'nullable', 'string', 'max:255'], 'terms' => 'accepted'];
    }
}
