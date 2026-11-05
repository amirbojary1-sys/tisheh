<?php

namespace App\Http\Requests;

class PortfolioRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('worker') ?? false;
    }

    public function rules(): array
    {
        return ['title' => 'required|string|max:120', 'description' => 'required|string|min:20|max:3000', 'completed_year' => 'nullable|integer|between:1300,2100', 'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096|dimensions:max_width=4096,max_height=4096'];
    }
}
