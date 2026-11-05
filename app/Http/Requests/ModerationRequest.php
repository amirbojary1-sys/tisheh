<?php

namespace App\Http\Requests;

class ModerationRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('admin.access') ?? false;
    }

    public function rules(): array
    {
        return ['status' => 'required|in:published,rejected,in_review', 'moderation_note' => 'required_if:status,rejected|nullable|string|max:1000'];
    }

    public function attributes(): array
    {
        return [...parent::attributes(), 'moderation_note' => 'دلیل رد آگهی'];
    }
}
