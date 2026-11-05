<?php

namespace App\Http\Requests;

use App\Support\Format;

class OtpVerifyRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        if (is_scalar($this->input('code'))) {
            $this->merge(['code' => Format::digits($this->input('code'))]);
        }
    }

    public function rules(): array
    {
        return ['code' => ['required', 'string', 'regex:/^[0-9]{4}$/']];
    }

    public function attributes(): array
    {
        return [...parent::attributes(), 'code' => 'کد چهاررقمی'];
    }
}
