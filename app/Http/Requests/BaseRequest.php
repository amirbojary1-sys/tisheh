<?php

namespace App\Http\Requests;

use App\Support\Format;
use Illuminate\Foundation\Http\FormRequest;

abstract class BaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['quantity', 'mobile', 'wage', 'price', 'stock', 'minimum_order', 'shipping_cost', 'workers_count', 'duration_days', 'national_id', 'contact_phone', 'postal_code', 'experience_years', 'completed_year'] as $key) {
            $value = $this->input($key);
            if ($this->has($key) && is_scalar($value)) {
                $this->merge([$key => Format::digits((string) $value)]);
            }
        }
    }

    public function attributes(): array
    {
        return ['name' => 'نام', 'last_name' => 'نام خانوادگی', 'national_id' => 'کد ملی', 'contact_phone' => 'شماره تماس', 'private_address' => 'آدرس خصوصی', 'postal_code' => 'کد پستی', 'headline' => 'عنوان حرفه‌ای', 'experience_years' => 'سابقه', 'activity_type' => 'نوع فعالیت', 'availability' => 'آمادگی همکاری', 'additional_roles' => 'نقش‌های دیگر', 'roles' => 'نقش‌ها', 'mobile' => 'شماره موبایل', 'current_password' => 'رمز فعلی', 'password_confirmation' => 'تکرار رمز جدید', 'password' => 'رمز عبور', 'role' => 'نقش', 'city' => 'شهر', 'specialty' => 'تخصص', 'business_name' => 'نام کسب‌وکار', 'title' => 'عنوان', 'description' => 'شرح کار', 'category_id' => 'دسته‌بندی', 'workers_count' => 'تعداد نیرو', 'duration_days' => 'مدت کار', 'wage' => 'دستمزد', 'start_date' => 'تاریخ شروع', 'address' => 'آدرس', 'brand' => 'برند', 'specifications' => 'مشخصات فنی', 'price' => 'قیمت', 'unit' => 'واحد', 'stock' => 'موجودی', 'minimum_order' => 'حداقل سفارش', 'shipping_cost' => 'هزینه حمل', 'image' => 'تصویر', 'message' => 'پیام', 'status' => 'وضعیت', 'payment_type' => 'نوع پرداخت', 'bio' => 'درباره شما', 'sale_terms' => 'شرایط فروش', 'skills' => 'مهارت‌ها'];
    }
}
