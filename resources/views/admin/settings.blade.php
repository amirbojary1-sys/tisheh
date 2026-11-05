@extends('layouts.admin')
@section('page','تنظیمات سامانه')
@section('admin-content')
<div class="admin-page-heading">
    <div>
        <h1>تنظیمات سامانه</h1>
        <p>ارتباط پشتیبانی و مقادیر پیشنهادی درآمدزایی؛ هویت مشکی و طلایی ثابت می‌ماند.</p>
    </div>
</div>

<form method="post" action="{{ route('admin.settings.update') }}" class="panel form-page">
    @csrf
    @method('put')
    <h2>راه‌های ارتباط و اطلاعیه</h2>
    <div class="form-grid">
        <x-field name="support_phone" label="تلفن پشتیبانی" :value="$settings['support_phone']??null" dir="ltr"/>
        <x-field name="support_email" label="ایمیل پشتیبانی" type="email" :value="$settings['support_email']??null" dir="ltr"/>
    </div>
    <x-textarea name="announcement" label="اطلاعیه عمومی (در صفحه راهنما نمایش داده می‌شود)" :value="$settings['announcement']??null"/>
    <h2 class="spaced">پیکربندی درآمدزایی آینده</h2>
    <div class="notice">مقادیر زیر ذخیره می‌شوند اما به‌معنی فعال‌شدن دریافت وجه نیستند. هزینه انتشار و اشتراک هنوز اعمال یا دریافت نمی‌شود. فعال‌سازی درگاه یا کیف پول از این فرم ممنوع است.</div>
    <div class="form-grid">
        <x-field name="publication_fee_rial" label="هزینه پیشنهادی انتشار (ریال)" type="number" min="0" required :value="$settings['publication_fee_rial']??0"/>
        <x-field name="subscription_fee_rial" label="هزینه پیشنهادی اشتراک (ریال)" type="number" min="0" required :value="$settings['subscription_fee_rial']??0"/>
    </div>
    <button class="button">ذخیره تنظیمات سامانه <x-icon name="check" size="17"/></button>
</form>

<form method="post" action="{{ route('password.change') }}" class="panel form-page spaced">
    @csrf
    @method('put')
    <h2>تغییر رمز عبور مدیر</h2>
    <p class="muted">برای تغییر رمز، رمز فعلی لازم است. رمز جدید باید حداقل ۱۲ نویسه و شامل حروف بزرگ، حروف کوچک و عدد باشد. پس از تغییر، همه نشست‌ها بسته می‌شوند و باید با رمز جدید دوباره وارد شوید.</p>
    <div class="form-grid">
        <x-field name="current_password" label="رمز فعلی" type="password" required autocomplete="current-password"/>
        <x-field name="password" label="رمز جدید" type="password" required autocomplete="new-password" minlength="12"/>
        <x-field name="password_confirmation" label="تکرار رمز جدید" type="password" required autocomplete="new-password" minlength="12"/>
    </div>
    <button class="button">تغییر رمز و خروج از نشست‌ها</button>
</form>
@endsection
