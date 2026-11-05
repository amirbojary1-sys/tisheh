<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartCheckoutRequest;
use App\Http\Requests\CartQuantityRequest;
use App\Models\MaterialProduct;
use App\Services\CartService;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(CartService $cart)
    {
        $items = $cart->items(auth()->user());

        return view('cart.index', compact('items'));
    }

    public function add(CartQuantityRequest $request, MaterialProduct $product, CartService $cart)
    {
        $cart->add($request->user(), $product->id, (int) $request->validated('quantity'));

        return redirect()->route('cart.index')->with('success', 'کالا به سبد خرید اضافه شد.');
    }

    public function update(CartQuantityRequest $request, MaterialProduct $product, CartService $cart)
    {
        $cart->setQuantity($request->user(), $product->id, (int) $request->validated('quantity'));

        return back()->with('success', 'تعداد کالا به‌روز شد.');
    }

    public function remove(MaterialProduct $product, CartService $cart)
    {
        $cart->remove(auth()->user(), $product->id);

        return back()->with('success', 'کالا از سبد خرید حذف شد.');
    }

    public function checkout(CartCheckoutRequest $request, CartService $cart)
    {
        $orders = $cart->checkout($request->user(), $request->validated());
        if ($orders->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'سفارشی برای نمایش وجود ندارد.']);
        }

        if ($orders->count() === 1) {
            return redirect()->route('orders.show', $orders->first())->with('success', 'سفارش ثبت شد؛ وضعیت پرداخت بانکی تأیید نشده است.');
        }

        return redirect()->route('orders.index')->with('success', 'سبد بین فروشندگان تفکیک و سفارش‌ها ثبت شدند؛ پرداخت بانکی تأیید نشده است.');
    }
}
