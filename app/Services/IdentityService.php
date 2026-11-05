<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use App\Support\IdentityFields;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IdentityService
{
    public function save(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data) {
            $p = UserProfile::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            $values = collect($data)->only(['contact_phone', 'private_address', 'postal_code'])->all();
            if ($data['clear_national_id'] ?? false) {
                $values['national_id'] = null;
                $values['national_id_fingerprint'] = null;
            } elseif (! empty($data['national_id'])) {
                $fingerprint = IdentityFields::fingerprint($data['national_id']);
                if (UserProfile::where('national_id_fingerprint', $fingerprint)->where('user_id', '!=', $user->id)->exists()) {
                    throw ValidationException::withMessages(['national_id' => 'این کد ملی قابل استفاده نیست؛ برای بررسی با پشتیبانی تماس بگیرید.']);
                }$values['national_id'] = $data['national_id'];
                $values['national_id_fingerprint'] = $fingerprint;
            }
            $p->fill($values);
            if (! $p->isDirty()) {
                return;
            }
            $p->identity_status = 'not_submitted';
            $p->identity_revision++;
            $p->identity_review_note = null;
            $p->identity_submitted_at = null;
            $p->identity_reviewed_at = null;
            $p->identity_reviewer_id = null;
            try {
                $p->save();
            } catch (UniqueConstraintViolationException $e) {
                throw ValidationException::withMessages(['national_id' => 'این کد ملی قابل استفاده نیست؛ با پشتیبانی تماس بگیرید.']);
            }
            app(AuditService::class)->record('identity.updated', $user, ['revision' => $p->identity_revision]);
        });
    }

    public function submit(User $user): void
    {
        DB::transaction(function () use ($user) {
            $p = UserProfile::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            foreach (['national_id', 'contact_phone', 'private_address', 'postal_code'] as $key) {
                if (empty($p->$key)) {
                    throw ValidationException::withMessages(['identity' => 'برای درخواست بررسی اداری، هر چهار فیلد را تکمیل کنید. استفاده از سامانه به آن وابسته نیست.']);
                }
            }abort_unless(in_array($p->identity_status, ['not_submitted', 'rejected']), 409);
            $p->update(['identity_status' => 'pending', 'identity_submitted_at' => now()]);
            app(AuditService::class)->record('identity.submitted', $user, ['revision' => $p->identity_revision]);
        });
    }

    public function review(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data) {
            $p = UserProfile::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            abort_unless($p->identity_status === 'pending' && $p->identity_revision === $data['revision'], 409);
            $p->update(['identity_status' => $data['decision'], 'identity_review_note' => $data['note'], 'identity_reviewed_at' => now(), 'identity_reviewer_id' => auth()->id()]);
            app(AuditService::class)->record('identity.reviewed', $user, ['revision' => $p->identity_revision, 'decision' => $data['decision']]);
            app(NoticeService::class)->send($user, 'نتیجه بررسی اداری اطلاعات', 'نتیجه بررسی خصوصی در بخش اطلاعات هویتی در دسترس است. این بررسی استعلام رسمی نیست.', '/account/identity');
        });
    }
}
