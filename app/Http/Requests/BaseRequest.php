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
        foreach (['quantity', 'mobile', 'wage', 'price', 'stock', 'minimum_order', 'shipping_cost', 'workers_count', 'duration_days'] as $key) {
            $value = $this->input($key);
            if ($this->has($key) && is_scalar($value)) {
                $this->merge([$key => Format::digits((string) $value)]);
            }
        }
    }

    public function attributes(): array
    {
        return ['name' => 'نام', 'last_name' => 'نام خانوادگی', 'mobile' => 'شماره موبایل', 'current_password' => 'رمز فعلی', 'password_confirmation' => 'تکرار رمز جدید', 'password' => 'رمز عبور', 'role' => 'نقش', 'city' => 'شهر', 'specialty' => 'تخصص', 'business_name' => 'نام کسب‌وکار', 'title' => 'عنوان', 'description' => 'شرح کار', 'category_id' => 'دسته‌بندی', 'workers_count' => 'تعداد نیرو', 'duration_days' => 'مدت کار', 'wage' => 'دستمزد', 'start_date' => 'تاریخ شروع', 'address' => 'آدرس', 'brand' => 'برند', 'specifications' => 'مشخصات فنی', 'price' => 'قیمت', 'unit' => 'واحد', 'stock' => 'موجودی', 'minimum_order' => 'حداقل سفارش', 'shipping_cost' => 'هزینه حمل', 'image' => 'تصویر', 'message' => 'پیام', 'status' => 'وضعیت', 'payment_type' => 'نوع پرداخت', 'bio' => 'درباره شما', 'sale_terms' => 'شرایط فروش', 'skills' => 'مهارت‌ها'];
    }
}
