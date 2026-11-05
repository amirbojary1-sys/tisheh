<?php

namespace App\Http\Requests;

class JobRequestForm extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('jobs.create') ?? false;
    }

    public function rules(): array
    {
        return ['title' => 'required|string|max:160', 'category_id' => 'required|exists:job_categories,id', 'description' => 'required|string|min:30|max:6000', 'skills' => 'nullable|string|max:2000', 'city' => 'required|string|max:80', 'address' => 'required|string|max:255', 'workers_count' => 'required|integer|between:1,500', 'start_date' => 'required|date_format:Y-m-d|after_or_equal:today', 'duration_days' => 'required|integer|between:1,3650', 'wage' => 'nullable|integer|min:10000|max:100000000000', 'payment_type' => 'required|in:daily,weekly,project', 'status' => 'required|in:draft,pending'];
    }
}
