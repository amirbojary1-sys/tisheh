@extends('layouts.app')
@section('title', 'سفارش‌های من | تیشه')
@section('content')
<div class="container page">
    <div class="section-heading">
        <div>
            <span class="eyebrow">از ثبت تا تحویل</span>
            <h1 class="page-title">سفارش‌های مصالح من</h1>
        </div>
        <div class="form-actions">
            <a class="button outline compact" href="{{ route('cart.index') }}">سبد خرید</a>
            <a class="button compact" href="{{ route('products.index') }}">بازار مصالح <x-icon name="cube" size="18" /></a>
        </div>
    </div>
    <div class="notice">سفارش‌های این بخش با روش پرداخت مستقیم هستند. هیچ انتقال وجه، رسید بانکی یا موجودی مالی توسط تیشه تأیید نمی‌شود.</div>
    <div class="grid three">
        @forelse($orders as $order)
            <a class="panel order-card" href="{{ route('orders.show', $order) }}">
                <div class="card-top"><span class="tag {{ $order->status }}">{{ \App\Support\Format::status($order->status) }}</span><x-icon name="cube" size="25" /></div>
                <h3>{{ $order->items->first()?->name }}</h3>
                @if($order->items->count() > 1)
                    <small class="muted">و {{ \App\Support\Format::number($order->items->count() - 1) }} کالای دیگر از همین فروشنده</small>
                @endif
                <p>{{ auth()->id() === $order->buyer_id ? 'خرید از '.$order->seller->full_name : 'فروش به '.$order->buyer->full_name }}</p>
                <strong class="gold">{{ $order->total === null ? 'حمل نیازمند تعیین' : \App\Support\Format::money($order->total) }}</strong>
                <small class="block">{{ \App\Support\Format::date($order->created_at) }} · #{{ \App\Support\Format::number($order->id) }}</small>
            </a>
        @empty
            <div class="empty">هنوز سفارشی ثبت نکرده‌اید.</div>
        @endforelse
    </div>
    {{ $orders->links('components.pagination') }}
</div>
@endsection
