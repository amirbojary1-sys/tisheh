@extends('layouts.app')
@section('title','ورود به تیشه')
@section('content')
<div class="container craft-auth-layout page">
    <aside class="craft-auth-story"><x-logo/><div><span class="eyebrow">نیروی کار، در کنار کار شما</span><h1>شروع یک همکاری خوب،<br><em>از همین‌جاست.</em></h1><p>جایی برای پیوند مهارت، پروژه و آینده‌ای که با هم می‌سازیم.</p></div><div class="craft-skyline" aria-hidden="true">@include('components.skyline')</div><small>تصویر نمایشی · سامانه کاریابی و بازار مصالح</small></aside>
    <section class="panel craft-auth-panel"><a class="auth-back text-link" href="{{ route('home') }}"><x-icon name="arrow" size="20"/> بازگشت به خانه</a><div class="auth-symbol"><x-icon name="helmet" size="36"/></div><span class="eyebrow">دوباره خوش آمدید</span><h1 class="page-title">ورود به تیشه</h1><p class="muted">مسیر همکاری‌تان را ادامه دهید.</p><div class="auth-methods"><a class="selected" href="{{ route('login') }}">رمز عبور</a><a href="{{ route('otp.form') }}">کد چهاررقمی آزمایشی</a></div>
        <form action="{{ route('login') }}" method="post">@csrf<x-field name="mobile" label="شماره موبایل" type="tel" required placeholder="۰۹۱۲…" autocomplete="username" dir="ltr"/>
        <div class="field"><label for="password">رمز عبور <span class="required">*</span></label><div class="password-input"><input id="password" name="password" type="password" required autocomplete="current-password"><button type="button" class="password-toggle" data-password-toggle="password" aria-controls="password" aria-label="نمایش رمز عبور" aria-pressed="false"><x-icon name="eye" size="20"/></button></div>@error('password')<span class="error">{{ $message }}</span>@enderror</div>
        <button type="submit" class="button full">ورود به حساب <x-icon name="arrow" size="18"/></button></form>
        <div class="auth-separator"><span>هنوز همراه ما نیستید؟</span></div><a class="button outline full" href="{{ route('register') }}">ساخت حساب جدید</a>
        <p class="auth-footnote">اطلاعات هویتی و آدرس پس از ورود از بخش «اطلاعات خصوصی» قابل تکمیل‌اند؛ برای ورود لازم نیستند. ورود مدیران با رمز عبور است. بازیابی خودکار رمز و سرویس پیامک واقعی هنوز فعال نیست.</p>
    </section>
</div>
@endsection
