<?php

namespace App\Services;

use App\Models\JobRequest;
use App\Models\MaterialProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ListingService
{
    public function saveJob(array $data, ?JobRequest $job = null): JobRequest
    {
        return DB::transaction(function () use ($data, $job) {
            $data['job_category_id'] = $data['category_id'];
            unset($data['category_id']);
            $job ??= new JobRequest(['user_id' => auth()->id()]);
            $job->fill($data);
            $job->moderation_note = null;
            $job->save();
            app(AuditService::class)->record('job.saved', $job, ['status' => $job->status]);
            if ($job->status === 'pending') {
                app(NoticeService::class)->admins('درخواست کار در انتظار بررسی', $job->title, '/admin/listings/job?status=pending');
            }

            return $job;
        });
    }

    public function saveProduct(array $data, ?MaterialProduct $product = null): MaterialProduct
    {
        $file = $data['image'] ?? null;
        unset($data['image']);
        $path = $file ? app(UploadedImageService::class)->store($file) : null;
        try {
            return DB::transaction(function () use ($data, $product, $path) {
                $data['material_category_id'] = $data['category_id'];
                unset($data['category_id']);
                if ($product?->exists) {
                    $product = MaterialProduct::whereKey($product->id)->lockForUpdate()->firstOrFail();
                }
                if ($path && $product?->exists && $product->images()->count() >= 8) {
                    throw ValidationException::withMessages(['image' => 'حداکثر ۸ تصویر مجاز است؛ ابتدا تصویر قبلی را حذف کنید.']);
                }
                $product ??= new MaterialProduct(['user_id' => auth()->id()]);
                $product->fill($data);
                $product->moderation_note = null;
                $product->save();
                if ($path) {
                    $product->images()->create(['path' => $path]);
                }app(AuditService::class)->record('product.saved', $product, ['status' => $product->status]);
                if ($product->status === 'pending') {
                    app(NoticeService::class)->admins('مصالح در انتظار بررسی', $product->name, '/admin/listings/product?status=pending');
                }

                return $product;
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }throw $e;
        }
    }

    public function apply(JobRequest $job, string $message): void
    {
        DB::transaction(function () use ($job, $message) {
            $locked = JobRequest::whereKey($job->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'published', 409);
            if ($locked->applications()->where('user_id', auth()->id())->exists()) {
                throw ValidationException::withMessages(['message' => 'قبلاً برای این فرصت اعلام آمادگی کرده‌اید.']);
            }$a = $locked->applications()->create(['user_id' => auth()->id(), 'message' => $message]);
            app(AuditService::class)->record('job.applied', $a);
            app(NoticeService::class)->send($locked->user, 'اعلام آمادگی جدید', $locked->title, '/jobs/'.$locked->id);
        });
    }
}
