<?php

namespace App\Services;

use App\Models\CartCheckout;
use App\Models\MaterialProduct;
use App\Models\ShoppingCart;
use App\Models\ShoppingCartItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function items(User $user): Collection
    {
        $cart = ShoppingCart::where('user_id', $user->id)->first();

        return $cart
            ? $cart->items()->with('product.user.location', 'product.images')->orderBy('id')->get()
            : new Collection;
    }

    public function add(User $user, int $productId, int $quantity): void
    {
        DB::transaction(function () use ($user, $productId, $quantity) {
            $buyer = $this->lockBuyer($user);
            $cart = $this->lockedCart($buyer);
            $product = MaterialProduct::with('user')->whereKey($productId)->lockForUpdate()->firstOrFail();
            abort_unless($product->status === 'published' && $product->user->status === 'active' && $product->user_id !== $buyer->id, 403);

            $item = $cart->items()->where('material_product_id', $product->id)->lockForUpdate()->first();
            $nextQuantity = ($item?->quantity ?? 0) + $quantity;
            if ($nextQuantity > $product->stock) {
                throw ValidationException::withMessages(['quantity' => 'تعداد انتخاب‌شده از موجودی فعلی بیشتر است.']);
            }

            if ($item) {
                $item->update(['quantity' => $nextQuantity]);
            } else {
                $cart->items()->create(['material_product_id' => $product->id, 'quantity' => $nextQuantity]);
            }
        }, 3);
    }

    public function setQuantity(User $user, int $productId, int $quantity): void
    {
        DB::transaction(function () use ($user, $productId, $quantity) {
            $buyer = $this->lockBuyer($user);
            $cart = $this->lockedCart($buyer);
            $item = $cart->items()->where('material_product_id', $productId)->lockForUpdate()->firstOrFail();
            $product = MaterialProduct::whereKey($productId)->lockForUpdate()->firstOrFail();

            if ($quantity > $product->stock) {
                throw ValidationException::withMessages(['quantity' => 'تعداد انتخاب‌شده از موجودی فعلی بیشتر است.']);
            }

            $item->update(['quantity' => $quantity]);
        }, 3);
    }

    public function remove(User $user, int $productId): void
    {
        DB::transaction(function () use ($user, $productId) {
            $buyer = $this->lockBuyer($user);
            $cart = $this->lockedCart($buyer);
            $cart->items()->where('material_product_id', $productId)->firstOrFail()->delete();
        }, 3);
    }

    public function checkout(User $user, array $data): Collection
    {
        return DB::transaction(function () use ($user, $data) {
            $buyer = $this->lockBuyer($user);
            $cart = $this->lockedCart($buyer);
            $existingCheckout = CartCheckout::with('orders')
                ->where('user_id', $buyer->id)
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();

            if ($existingCheckout) {
                return $existingCheckout->orders;
            }

            $items = $cart->items()->with('product.user')->orderBy('material_product_id')->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'سبد خرید خالی است.']);
            }

            $products = MaterialProduct::with('user')->whereIn('id', $items->pluck('material_product_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            abort_unless($products->count() === $items->count(), 409);

            foreach ($items as $item) {
                $product = $products->get($item->material_product_id);
                if ($product->status !== 'published' || $product->user->status !== 'active' || $product->user_id === $buyer->id) {
                    throw ValidationException::withMessages(['cart' => 'یکی از کالاها دیگر قابل سفارش نیست؛ آن را از سبد حذف کنید.']);
                }
                if ($item->quantity < $product->minimum_order || $item->quantity > $product->stock) {
                    throw ValidationException::withMessages(['cart' => 'موجودی یا حداقل سفارش یکی از کالاها تغییر کرده است؛ سبد را اصلاح کنید.']);
                }
            }

            $orders = new Collection;
            $groups = $items->groupBy(fn (ShoppingCartItem $item) => $products->get($item->material_product_id)->user_id)->sortKeys();
            $orderService = app(OrderService::class);
            foreach ($groups as $sellerId => $sellerItems) {
                $quantities = $sellerItems->mapWithKeys(fn (ShoppingCartItem $item) => [$item->material_product_id => $item->quantity])->all();
                $orders->push($orderService->createForCart(
                    $buyer,
                    $quantities,
                    $data,
                    (string) Str::uuid(),
                ));
            }

            $checkout = CartCheckout::create(['user_id' => $buyer->id, 'idempotency_key' => $data['idempotency_key']]);
            $checkout->orders()->attach($orders->modelKeys());
            $cart->items()->delete();

            return $orders;
        }, 3);
    }

    private function lockBuyer(User $user): User
    {
        $buyer = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
        abort_unless($buyer->status === 'active', 403);

        return $buyer;
    }

    private function lockedCart(User $buyer): ShoppingCart
    {
        $cart = ShoppingCart::firstOrCreate(['user_id' => $buyer->id]);

        return ShoppingCart::whereKey($cart->id)->lockForUpdate()->firstOrFail();
    }
}
