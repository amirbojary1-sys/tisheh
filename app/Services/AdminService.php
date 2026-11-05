<?php

namespace App\Services;

use App\Models\JobCategory;
use App\Models\JobRequest;
use App\Models\MaterialCategory;
use App\Models\MaterialProduct;
use App\Models\Report;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Support\Format;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminService
{
    public function user(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data) {
            $u = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_if($u->hasRole('admin'), 403);
            $before = ['status' => $u->status, 'role_id' => $u->role_id];
            if (isset($data['role_id'])) {
                $role = Role::findOrFail($data['role_id']);
                abort_if($role->name === 'admin' || $role->id !== $u->role_id, 403);
            } // Role migration needs a separate verified workflow; not a form field.
            $u->fill(collect($data)->only('name', 'last_name')->all());
            $u->status = $data['status'];
            $u->save();
            if (isset($data['city'])) {
                $u->profile()->update(['city' => $data['city']]);
            }
            if ($u->status === 'suspended') {
                DB::table('sessions')->where('user_id', $u->id)->delete();
            }app(AuditService::class)->record('user.status_changed', $u, ['before' => $before, 'after' => ['status' => $u->status, 'role_id' => $u->role_id], 'reason' => $data['reason'] ?? null]);
        });
    }

    public function moderate(string $type, int $id, array $data, bool $pendingOnly = true): void
    {
        DB::transaction(function () use ($type, $id, $data, $pendingOnly) {
            $m = ($type === 'job' ? JobRequest::query() : MaterialProduct::query())->lockForUpdate()->findOrFail($id);
            if ($pendingOnly) {
                abort_unless(in_array($m->status, ['pending', 'in_review'], true), 409);
            }$before = $m->status;
            $m->update($data);
            app(AuditService::class)->record($type.'.moderated', $m, ['before' => $before, ...$data]);
            app(NoticeService::class)->send($m->user, 'نتیجه بررسی آگهی', Format::status($m->status), ($type === 'job' ? '/jobs/' : '/materials/').$m->id);
        });
    }

    public function category(string $type, ?int $id, array $data, bool $delete = false): void
    {
        $class = $type === 'job' ? JobCategory::class : MaterialCategory::class;
        DB::transaction(function () use ($type, $id, $data, $delete, $class) {
            $m = $id ? $class::whereKey($id)->lockForUpdate()->firstOrFail() : new $class;
            if ($delete) {
                $used = ($type === 'job' ? JobRequest::query() : MaterialProduct::query())->where($type === 'job' ? 'job_category_id' : 'material_category_id', $id)->exists();
                if ($used) {
                    throw ValidationException::withMessages(['category' => 'دسته‌بندی دارای آگهی قابل حذف نیست.']);
                }app(AuditService::class)->record('category.deleted', $m, ['name' => $m->name]);
                $m->delete();
            } else {
                $m->fill($data)->save();
                app(AuditService::class)->record('category.saved', $m, ['name' => $m->name]);
            }
        });
    }

    public function report(Report $report, array $data): void
    {
        DB::transaction(function () use ($report, $data) {
            $report->update([...$data, 'assigned_to' => auth()->id()]);
            app(AuditService::class)->record('report.reviewed', $report, $data);
            app(NoticeService::class)->send($report->user, 'پاسخ به گزارش شما', $data['admin_response'], '/reports');
        });
    }

    public function settings(array $data): void
    {
        DB::transaction(function () use ($data) {
            foreach ($data as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value ?? '']);
            }app(AuditService::class)->record('settings.updated', auth()->user(), ['keys' => array_keys($data)]);
        });
    }
}
