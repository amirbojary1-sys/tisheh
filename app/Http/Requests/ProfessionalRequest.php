<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ProfessionalRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('worker') ?? false;
    }

    public function rules(): array
    {
        return ['headline' => 'required|string|max:120', 'city' => 'required|string|max:80', 'specialty' => 'required|string|max:100', 'bio' => 'required|string|min:20|max:2000', 'activity_type' => ['required', Rule::in(array_keys(config('network.activities')))], 'experience_years' => 'required|integer|between:0,70', 'availability' => ['required', Rule::in(array_keys(config('network.availability')))], 'skills' => 'required|array|min:1|max:12', 'skills.*' => 'required|integer|distinct|exists:job_categories,id', 'directory_enabled' => 'required|boolean', 'public_contact' => 'required|boolean'];
    }
}
