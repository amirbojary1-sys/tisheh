@props(['product', 'compare' => false])
<article class="product-card">
    <a href="{{ route('products.show', $product) }}" class="product-image">
        @if($product->images->first())
            <img src="{{ asset('storage/'.$product->images->first()->path) }}" alt="{{ $product->name }}" loading="lazy">
        @else
            <div class="material-art art-{{ $product->category->slug }}"><x-icon name="cube" size="76" /><span>{{ $product->category->name }}</span></div>
        @endif
        <span class="stock-tag">{{ $product->stock ? 'موجود' : 'ناموجود' }}</span>
    </a>
    <div class="product-body">
        <small>{{ $product->brand }} <span class="separator">|</span> {{ $product->category->name }}</small>
        <a class="card-title" href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
        <div class="meta"><span><x-icon name="pin" size="15" />{{ $product->city }}</span><span>{{ $product->user->location?->name ?? $product->user->full_name }}</span></div>
        <div class="product-price"><strong>{{ \App\Support\Format::money($product->price) }}</strong><small>/ {{ config('tisheh.units.'.$product->unit) }}</small></div>
        <div class="product-foot">
            <small>حمل: {{ $product->shipping_cost === null ? 'نیازمند استعلام' : \App\Support\Format::money($product->shipping_cost) }}</small>
            @if($compare)
                <label class="compare-check"><input type="checkbox" name="ids[]" value="{{ $product->id }}" form="compare-form"> مقایسه</label>
            @else
                <a class="text-link" href="{{ route('products.show', $product) }}">جزئیات <x-icon name="arrow" size="14" /></a>
            @endif
        </div>
    </div>
</article>
