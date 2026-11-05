<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#050607">
    <meta name="description" content="تیشه؛ ارتباط نیروی متخصص، کارفرما و بازار مصالح">
    <title>{{ trim($__env->yieldContent('title','تیشه | کار، مصالح، آینده بهتر')) }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/reference.css') }}">
    <script defer src="{{ asset('js/app.js') }}"></script>
<link rel="stylesheet" href="{{ asset('css/craft.css') }}"><script defer src="{{ asset('js/craft.js') }}"></script></head>
<body class="reference-app craft-app {{ request()->routeIs('home')?'is-home':'' }} {{ request()->routeIs('login','register','otp.*')?'is-auth':'' }}" data-page="{{ request()->route()?->getName() }}">
    <a class="skip" href="#main">رفتن به محتوای اصلی</a>
    @auth
        @php($cartCount = auth()->user()->cart?->items()->sum('quantity') ?? 0)
    @endauth
    <header class="app-header">
        <div class="container shell-top">
            <details class="app-menu">
                <summary aria-label="بازکردن منو"><x-icon name="menu" /></summary>
                <nav><div class="drawer-heading">همراه شما در مسیر ساختن</div>
                    <a href="{{ route('home') }}">خانه</a>
                    <a href="{{ route('jobs.index') }}">فرصت‌های کار</a>
                    <a href="{{ route('professionals.index') }}">متخصصان ساختمان</a><a href="{{ route('products.index') }}">بازار مصالح</a>
                    @auth<a href="{{ route('cart.index') }}">سبد خرید ({{ \App\Support\Format::number($cartCount) }})</a>@endauth
                    <a href="{{ route('orders.index') }}">سفارش‌های من</a>
                    <a href="{{ route('reports.index') }}">پشتیبانی و گزارش‌ها</a>
                    <a href="{{ route('content.show', 'help') }}">راهنمای تیشه</a>
                    @auth
                        @if(auth()->user()->hasPermission('admin.access'))
                            <a href="{{ route('admin') }}">پنل مدیریت</a>
                        @endif
                    @endauth
                </nav>
            </details>
            <x-logo />
            <nav class="desktop-nav">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">خانه</a>
                <a href="{{ route('jobs.index') }}" class="{{ request()->routeIs('jobs.*') ? 'active' : '' }}">فرصت‌های کاری</a>
                <a href="{{ route('professionals.index') }}" class="{{ request()->routeIs('professionals.*')?'active':'' }}">متخصصان</a>
                <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'active' : '' }}">بازار مصالح</a>
                @auth<a href="{{ route('cart.index') }}" class="{{ request()->routeIs('cart.*') ? 'active' : '' }}">سبد خرید</a>@endauth
                <a href="{{ route('orders.index') }}">سفارش‌ها</a>
                <a href="{{ route('reports.index') }}">پشتیبانی</a>
            </nav>
            <div class="header-actions">
                @auth
                    <a class="header-bell" href="{{ route('notifications') }}" aria-label="اعلان‌ها">
                        <x-icon name="bell" />
                        @if(auth()->user()->unreadNotifications()->exists())<i></i>@endif
                    </a>
                    <a class="button outline compact desktop-account" href="{{ auth()->user()->hasRole('admin') ? route('admin') : route('dashboard') }}">
                        <x-icon name="user" size="17" />
                        {{ auth()->user()->hasRole('admin') ? 'پنل مدیریت' : 'حساب من' }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="header-guest icon-button" aria-label="ورود به حساب"><x-icon name="user" size="21"/><span>ورود / عضویت</span></a>
                @endauth
            </div>
        </div>
    </header>
    <main id="main">
        @if(session('success'))
            <div class="container"><div class="alert success" role="status"><x-icon name="check" />{{ session('success') }}</div></div>
        @endif
        @if($errors->any())
            <div class="container">
                <div class="alert" role="alert">
                    اطلاعات فرم را بررسی کنید.
                    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            </div>
        @endif
        @yield('content')
    </main>
    <footer class="app-footer">
        <div class="container">
            <x-logo />
            <p>پیوند مهارت، فرصت و مصالح. برای ساختن فردایی محکم‌تر.</p>
            <div class="footer-links">
                <a href="{{ route('terms') }}">حریم خصوصی و شرایط</a>
                <a href="{{ route('reports.index') }}">پشتیبانی و شکایت</a>
                <a href="{{ route('content.show', 'help') }}">راهنمای استفاده</a>
                <a href="{{ route('content.show', 'about') }}">درباره تیشه</a>
            </div>
            <small>نسخه توسعه • آگهی‌های اولیه نمونه‌اند • پرداخت بانکی و کیف پول فعال نیست.</small>
        </div>
    </footer>
    <nav class="mobile-nav app-bottom" aria-label="منوی پایین">
        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}"><x-icon name="home" /><span>خانه</span></a>
        <a href="{{ route('jobs.index') }}" class="{{ request()->routeIs('jobs.*') ? 'active' : '' }}"><x-icon name="briefcase" /><span>درخواست‌ها</span></a>
        <a class="nav-add" href="{{ auth()->check()?route('account.actions'):route('register') }}" aria-label="فعالیت جدید"><span><x-icon name="plus" size="29" /></span></a>
        <a href="{{ route('messages.index') }}" class="{{ request()->routeIs('messages.*') ? 'active' : '' }}"><x-icon name="message" /><span>پیام‌ها</span></a>
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><x-icon name="user" /><span>پروفایل</span></a>
    </nav>
</body>
</html>
