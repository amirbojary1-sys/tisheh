<?php

namespace App\Services;

use App\Models\PortfolioEntry;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DirectoryService
{
    public function publicQuery()
    {
        return User::query()->where('status', 'active')->where(function ($q) {
            $q->whereHas('role', fn ($r) => $r->where('name', 'worker'))->orWhereHas('roles', fn ($r) => $r->where('name', 'worker'));
        })->whereHas('profile', fn ($p) => $p->where('directory_enabled', true)->where('directory_status', 'published'));
    }

    public function visible(User $user): bool
    {
        return $this->publicQuery()->whereKey($user->id)->exists();
    }

    public function invalidate(User $user): void
    {
        $p = UserProfile::where('user_id', $user->id)->lockForUpdate()->first();
        if ($p) {
            $p->directory_revision++;
            if ($p->directory_enabled) {
                $p->directory_status = 'pending';
            }$p->directory_review_note = null;
            $p->save();
        }
    }

    public function save(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $p = UserProfile::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            $p->fill(collect($data)->except('skills')->all());
            $p->directory_revision++;
            $p->directory_status = $data['directory_enabled'] ? 'pending' : 'draft';
            $p->directory_review_note = null;
            $p->save();
            $user->skills()->sync($data['skills']);
            app(AuditService::class)->record('professional.submitted', $user, ['revision' => $p->directory_revision, 'enabled' => (bool) $data['directory_enabled']]);
        });
    }

    public function review(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data) {
            $p = UserProfile::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            abort_unless($p->directory_enabled && ($p->directory_status === 'pending' || ($p->directory_status === 'published' && $data['decision'] === 'rejected')) && $p->directory_revision === $data['revision'], 409);
            abort_unless($user->status === 'active' && $user->hasRole('worker'), 409);
            $p->update(['directory_status' => $data['decision'], 'directory_review_note' => $data['note']]);
            app(AuditService::class)->record('professional.reviewed', $user, ['decision' => $data['decision'], 'revision' => $p->directory_revision]);
            app(NoticeService::class)->send($user, 'نتیجه بررسی پروفایل حرفه‌ای', 'وضعیت انتشار پروفایل شما تغییر کرد.', '/account/professional');
        });
    }

    public function addPortfolio(User $user, array $data, ?UploadedFile $file): void
    {
        $path = null;
        if ($file) {
            $image = @imagecreatefromstring(file_get_contents($file->getRealPath()));
            if (! $image) {
                throw ValidationException::withMessages(['image' => 'تصویر قابل پردازش نیست.']);
            }try {
                $w = imagesx($image);
                $h = imagesy($image);
                if (max($w, $h) > 1600) {
                    $s = 1600 / max($w, $h);
                    $scaled = imagescale($image, (int) ($w * $s), (int) ($h * $s));
                    if (! $scaled) {
                        throw new \RuntimeException('Image resize failed.');
                    }imagedestroy($image);
                    $image = $scaled;
                }ob_start();
                try {
                    if (! imagewebp($image, null, 85)) {
                        throw new \RuntimeException('Image encode failed.');
                    }$bytes = ob_get_contents();
                } finally {
                    ob_end_clean();
                }
            } finally {
                imagedestroy($image);
            }$path = 'portfolio/'.Str::uuid().'.webp';
            if (! Storage::disk('local')->put($path, $bytes)) {
                throw new \RuntimeException('Image storage failed.');
            }
        }
        try {
            DB::transaction(function () use ($user, $data, $path) {
                User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                if ($user->portfolioEntries()->count() >= 8) {
                    throw ValidationException::withMessages(['portfolio' => 'حداکثر ۸ نمونه‌کار مجاز است.']);
                }$entry = $user->portfolioEntries()->create([...collect($data)->only(['title', 'description', 'completed_year'])->all(), 'image_path' => $path]);
                $this->invalidate($user);
                app(AuditService::class)->record('portfolio.created', $entry);
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }throw $e;
        }
    }

    public function updatePortfolio(User $user, PortfolioEntry $entry, array $data): void
    {
        abort_unless($entry->user_id === $user->id, 403);
        if (isset($data['image'])) {
            throw ValidationException::withMessages(['image' => 'برای تغییر تصویر، نمونه‌کار را حذف و دوباره اضافه کنید.']);
        }DB::transaction(function () use ($user, $entry, $data) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $e = PortfolioEntry::whereKey($entry->id)->lockForUpdate()->firstOrFail();
            $e->update(collect($data)->only(['title', 'description', 'completed_year'])->all());
            $this->invalidate($user);
            app(AuditService::class)->record('portfolio.updated', $e);
        });
    }

    public function deletePortfolio(User $user, PortfolioEntry $entry): void
    {
        abort_unless($user->id === $entry->user_id, 403);
        $path = $entry->image_path;
        DB::transaction(function () use ($user, $entry) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            app(AuditService::class)->record('portfolio.deleted', $entry);
            $entry->delete();
            $this->invalidate($user);
        });
        if ($path) {
            Storage::disk('local')->delete($path);
        }
    }
}
