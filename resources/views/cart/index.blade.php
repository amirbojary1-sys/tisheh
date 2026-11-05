@extends('layouts.app')
@section('title', 'سبد خرید | تیشه')
@section('content')
<div class="container page">
    <div class="section-heading">
        <div>
            <span class="eyebrow">خرید چندفروشنده‌ای</span>
            <h1 class="page-title">سبد خرید</h1>
        </div>
        <a class="button compact" href="{{ route('products.index') }}">بازار مصالح</a>
    </div>

    <p class="notice">سبد به حساب شما متصل است و پس از خروج از حساب نیز باقی می‌ماند. هنگام ثبت، کالاها بر اساس فروشنده به سفارش‌های جداگانه تقسیم می‌شوند؛ هیچ پرداخت بانکی انجام یا تأیید نمی‌شود.</p>

    @if($items->isEmpty())
        <section class="panel empty">
            <h2>سبد خرید خالی است</h2>
            <p class="muted">از بازار مصالح کالاهای موردنیاز را اضافه کنید.</p>
            <a class="button" href="{{ route('products.index') }}">مشاهده بازار مصالح</a>
        </section>
    @else
        @php($sellerGroups = $items->groupBy(fn ($item) => $item->product->user_id))
        @foreach($sellerGroups as $sellerItems)
            @php($seller = $sellerItems->first()->product->user)
            <section class="panel spaced">
                <h2>فروشنده: {{ $seller->location?->name ?? $seller->full_name }}</h2>
                @foreach($sellerItems as $item)
                    @php($product = $item->product)
                    <div class="list-row">
                        <div>
                            <a class="card-title" href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
                            <p class="muted">{{ $product->brand }} · {{ \App\Support\Format::money($product->price) }} / {{ config('tisheh.units.'.$product->unit) }}</p>
                            <small>حداقل سفارش {{ \App\Support\Format::number($product->minimum_order) }} · موجودی فعلی {{ \App\Support\Format::number($product->stock) }}</small>
                        </div>
                        <strong class="gold">{{ \App\Support\Format::money($product->price * $item->quantity) }}</strong>
                        <div class="cart-item-actions">
                            <form method="post" action="{{ route('cart.items.update', $product) }}">
                                @csrf
                                @method('patch')
                                <label for="quantity-{{ $product->id }}">تعداد</label>
                                <input id="quantity-{{ $product->id }}" name="quantity" type="number" min="1" max="{{ max(1, $product->stock) }}" value="{{ $item->quantity }}" required>
                                <button class="button compact" type="submit">به‌روزرسانی</button>
                            </form>
                            <form method="post" action="{{ route('cart.items.remove', $product) }}">
                                @csrf
                                @method('delete')
                                <button class="button outline compact" type="submit">حذف کالا</button>
                            </form>
                        </div>
                    </div>
                @endforeach
                @php($sellerSubtotal = $sellerItems->sum(fn ($item) => $item->quantity * $item->product->price))
                <div class="invoice-line total">
                    <span>جمع کالاهای این فروشنده</span>
                    <strong>{{ \App\Support\Format::money($sellerSubtotal) }}</strong>
                </div>
                @if($sellerItems->count() > 1)
                    <p class="muted">هزینه حمل اقلام چندگانه پس از بررسی فروشنده اعلام می‌شود.</p>
                @else
                    <p class="muted">هزینه حمل: {{ $sellerItems->first()->product->shipping_cost === null ? 'پس از بررسی فروشنده اعلام می‌شود' : \App\Support\Format::money($sellerItems->first()->product->shipping_cost) }}</p>
                @endif
            </section>
        @endforeach

        <section class="panel spaced">
            <h2>ثبت سفارش‌ها</h2>
            <p class="notice">یک آدرس تحویل برای سفارش‌های این سبد استفاده می‌شود و فقط خریدار و فروشندهٔ مربوط به هر سفارش آن را می‌بینند. سفارش همهٔ فروشندگان یک‌جا ثبت می‌شود؛ اگر موجودی هر کالا کافی نباشد، هیچ سفارشی ثبت و هیچ موجودی‌ای رزرو نخواهد شد.</p>
            <form method="post" action="{{ route('cart.checkout') }}">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
                <x-textarea name="delivery_address" label="آدرس تحویل (برای همه سفارش‌های این سبد)" required minlength="10" />
                <x-textarea name="note" label="یادداشت سفارش" />
                <button class="button" type="submit">ثبت سفارش برای {{ \App\Support\Format::number($sellerGroups->count()) }} فروشنده</button>
            </form>
        </section>
    @endif
</div>
@endsection
