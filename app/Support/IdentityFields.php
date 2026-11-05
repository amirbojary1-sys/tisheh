<?php

namespace App\Support;

use App\Rules\IranNationalId;

class IdentityFields
{
    public static function rules(): array
    {
        return ['national_id' => ['nullable', 'string', new IranNationalId], 'contact_phone' => ['nullable', 'string', 'regex:/^0[1-9][0-9]{9}$/D'], 'private_address' => 'nullable|string|min:10|max:1000', 'postal_code' => ['nullable', 'string', 'regex:/^[1-9][0-9]{9}$/D', 'not_regex:/^(\d)\1{9}$/D']];
    }

    public static function fingerprint(string $id): string
    {
        return hash_hmac('sha256', $id, (string) config('app.key'));
    }
}
