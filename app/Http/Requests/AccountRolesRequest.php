<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class AccountRolesRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['roles' => 'required|array|min:1|max:3', 'roles.*' => ['required', 'distinct', Rule::in(['worker', 'employer', 'seller'])], 'business_name' => [Rule::requiredIf(fn () => is_array($this->input('roles')) && in_array('seller', $this->input('roles'))), 'nullable', 'string', 'max:120'], 'address' => [Rule::requiredIf(fn () => is_array($this->input('roles')) && in_array('seller', $this->input('roles'))), 'nullable', 'string', 'max:255']];
    }
}
