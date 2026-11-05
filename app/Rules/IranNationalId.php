<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IranNationalId implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^[0-9]{10}$/D', $value) || preg_match('/^(\d)\1{9}$/D', $value)) {
            $fail('کد ملی باید ده رقم معتبر و غیرتکراری باشد.');

            return;
        }
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $value[$i] * (10 - $i);
        }$r = $sum % 11;
        if ((int) $value[9] !== ($r < 2 ? $r : 11 - $r)) {
            $fail('ساختار کد ملی معتبر نیست. اعتبار ساختاری به معنای احراز مالکیت نیست.');
        }
    }
}
