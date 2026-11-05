@extends('layouts.app')
@section('title', $product->name.' | تیشه')
@section('content')
<div class="container page">
    <a class="text-link" href="{{ route('products.index') }}">بازار مصالح / {{ $product->category->name }}</a>
    <div class="detail-grid">
        <section class="panel">
            <span class="tag">{{ \App\Support\Format::status($product->status) }}</span>
            <h1 class="page-title">{{ $product->name }}</h1>
            <p class="muted">{{ $product->brand }} · {{ $product->city }} · به‌روزرسانی {{ \App\Support\Format::date($product->updated_at) }}</p>
            @foreach($product->images as $image)
                <img class="detail-image" src="{{ asset('storage/'.$image->path) }}" alt="{{ $product->name }}" loading="lazy">
            @endforeach
            <h3>مشخصات فنی (اعلام فروشنده)</h3>
            <p class="pre-line">{{ $product->specifications }}</p>
            <div class="facts">
                <div><small>موجودی</small><strong>{{ \App\Support\Format::number($product->stock) }} {{ config('tisheh.units.'.$product->unit) }}</strong></div>
                <div><small>حداقل سفارش</small><strong>{{ \App\Support\Format::number($product->minimum_order) }}</strong></div>
                <div><small>هزینه حمل اعلامی</small><strong>{{ $product->shipping_cost === null ? 'نیازمند استعلام' : \App\Support\Format::money($product->shipping_cost) }}</strong></div>
            </div>
            <h3>شرایط فروش و ارسال</h3>
            <p class="pre-line">{{ $product->sale_terms ?? 'شرایط با فروشنده هماهنگ شود.' }}</p>
            @if($product->moderation_note)<div class="alert">یادداشت بررسی: {{ $product->moderation_note }}</div>@endif
            @can('update', $product)
                <div class="form-actions">
                    <a class="button" href="{{ route('products.edit', $product) }}">ویرایش قیمت و موجودی</a>
                    <form method="post" action="{{ route('products.deactivate', $product) }}">@csrf<button class="button outline">غیرفعال کردن آگهی</button></form>
                </div>
            @endcan
        </section>
        <aside class="panel detail-aside">
            <div class="eyebrow">قیمت پیشنهادی فروشنده</div>
            <h2 class="gold">{{ \App\Support\Format::money($product->price) }}</h2>
            <p>هر {{ config('tisheh.units.'.$product->unit) }}</p>
            <hr>
            <h3>{{ $product->user->location?->name ?? $product->user->full_name }}</h3>
            <p><x-icon name="pin" size="17" /> {{ $product->user->location?->address ?? $product->city }}</p>
            @auth
                @if($product->status === 'published' && auth()->id() !== $product->user_id)
                    <form method="post" action="{{ route('messages.start', ['product', $product->id]) }}">@csrf<button class="button outline full"><x-icon name="message" size="18" /> پیام به فروشنده</button></form>
                    <form method="post" action="{{ route('cart.items.add', $product) }}" class="spaced">
                        @csrf
                        <x-field name="quantity" label="تعداد برای افزودن به سبد" type="number" min="1" :max="max(1, $product->stock)" :value="min($product->minimum_order, max(1, $product->stock))" required />
                        <button class="button full" type="submit" @disabled($product->stock < $product->minimum_order)>افزودن به سبد خرید</button>
                    </form>
                @endif
                <a class="button full" href="tel:{{ $product->user->mobile }}"><x-icon name="phone" size="18" /> تماس با فروشنده</a>
            @else
                <a class="button full" href="{{ route('login') }}">ورود برای تماس و خرید</a>
            @endauth
            <a class="button outline full" target="_blank" rel="noopener noreferrer" href="{{ 'https://www.google.com/maps/search/?api=1&query='.urlencode($product->city.' '.($product->user->location?->address ?? '')) }}">جستجوی آدرس روی نقشه <x-icon name="pin" size="18" /></a>
            <p class="notice">این لینک، جستجوی آدرس است و موقعیت تأییدشده فروشگاه نیست. پرداخت بانکی و کیف پول هنوز فعال نیست.</p>
            @auth<a class="text-link spaced block" href="{{ route('reports.create', ['type' => 'product', 'id' => $product->id]) }}">گزارش این آگهی <x-icon name="flag" size="16" /></a>@endauth
        </aside>
    </div>
    @auth
        @if($product->status === 'published' && auth()->id() !== $product->user_id)
            <section class="panel form-page" id="order">
                <div class="section-heading"><h2>ثبت سفارش مستقیم</h2><span class="tag">بدون پرداخت آنلاین</span></div>
                <p class="notice">برای سفارش مستقیم، موجودی پس از ثبت رزرو می‌شود. هیچ مبلغی در سایت دریافت نمی‌شود؛ پرداخت خارج از سامانه و تأییدنشده خواهد بود.</p>
                <form method="post" action="{{ route('orders.store', $product) }}">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
                    <input type="hidden" name="payment_method" value="direct">
                    <x-field name="quantity" label="مقدار سفارش (واحد فروش بالا)" type="number" :min="$product->minimum_order" :max="$product->stock" :value="$product->minimum_order" required />
                    <x-textarea name="delivery_address" label="آدرس تحویل (فقط طرفین سفارش و مدیر)" required minlength="10" />
                    <x-textarea name="note" label="یادداشت سفارش" />
                    <button class="button" @disabled($product->stock < $product->minimum_order)>ثبت سفارش مستقیم <x-icon name="arrow" size="18" /></button>
                </form>
            </section>
        @endif
    @endauth
</div>
@endsection
