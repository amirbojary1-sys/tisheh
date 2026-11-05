<?php

namespace App\Services;

use App\Models\MaterialOrder;
use App\Models\MaterialProduct;
use App\Models\User;
use App\Support\Format;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function create(User $buyer, MaterialProduct $product, array $data): MaterialOrder
    {
        return $this->createForProducts($buyer, [$product->id => (int) $data['quantity']], $data, $data['idempotency_key']);
    }

    public function createForCart(User $buyer, array $quantities, array $data, string $idempotencyKey): MaterialOrder
    {
        return $this->createForProducts($buyer, $quantities, $data, $idempotencyKey);
    }

    private function createForProducts(User $buyer, array $quantities, array $data, string $idempotencyKey): MaterialOrder
    {
        return DB::transaction(function () use ($buyer, $quantities, $data, $idempotencyKey) {
            $lockedBuyer = User::whereKey($buyer->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedBuyer->status === 'active', 403);
            $products = MaterialProduct::with('user')->whereIn('id', array_keys($quantities))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            abort_unless($products->count() === count($quantities), 404);
            $existing = MaterialOrder::where('buyer_id', $buyer->id)->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                $existingIds = $existing->items()->orderBy('material_product_id')->pluck('material_product_id')->map(fn ($id) => (int) $id)->all();
                $requestedIds = array_map('intval', array_keys($quantities));
                sort($requestedIds);
                abort_unless($existingIds === $requestedIds, 409);

                return $existing;
            }
            $sellerIds = $products->pluck('user_id')->unique();
            abort_unless($sellerIds->count() === 1, 409);
            $subtotal = 0;
            $lineItems = [];
            foreach ($products as $product) {
                abort_unless($product->status === 'published' && $product->user->status === 'active' && $product->user_id !== $buyer->id, 403);
                $quantity = (int) ($quantities[$product->id] ?? 0);
                if ($quantity < $product->minimum_order || $quantity > $product->stock) {
                    throw ValidationException::withMessages(['quantity' => 'مقدار هر کالا باید حداقل سفارش و موجودی فعلی فروشنده را رعایت کند.']);
                }
                $lineTotal = $product->price * $quantity;
                if ($lineTotal > 100000000000000 || $subtotal > 100000000000000 - $lineTotal) {
                    throw ValidationException::withMessages(['quantity' => 'مبلغ سفارش از سقف مجاز این نسخه بیشتر است.']);
                }
                $subtotal += $lineTotal;
                $lineItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ];
            }
            $shippingCost = $products->count() === 1 ? $products->first()->shipping_cost : null;
            $seller = $products->first()->user;
            $order = MaterialOrder::create([
                'reference' => (string) Str::uuid(),
                'idempotency_key' => $idempotencyKey,
                'buyer_id' => $buyer->id,
                'seller_id' => $seller->id,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'total' => $shippingCost === null ? null : $subtotal + $shippingCost,
                'delivery_address' => $data['delivery_address'],
                'note' => $data['note'] ?? null,
                'quote_accepted_at' => $shippingCost === null ? null : now(),
                'reservation_expires_at' => now()->addHours(config('orders.reservation_hours')),
            ]);
            foreach ($lineItems as $line) {
                $product = $line['product'];
                $order->items()->create([
                    'material_product_id' => $product->id,
                    'name' => $product->name,
                    'brand' => $product->brand,
                    'unit' => $product->unit,
                    'unit_price' => $product->price,
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ]);
                $product->decrement('stock', $line['quantity']);
            }
            $order->events()->create(['user_id' => $buyer->id, 'status' => 'awaiting_seller', 'note' => 'سفارش مستقیم ثبت و موجودی رزرو شد؛ پرداخت بانکی تأیید نشده است.']);
            app(AuditService::class)->record('order.created', $order, ['quantity' => array_sum($quantities), 'items' => count($lineItems)]);
            app(NoticeService::class)->send($seller, 'سفارش جدید مصالح', $order->items()->pluck('name')->implode('، '), '/orders/'.$order->id);

            return $order;
        });
    }

    public function transition(User $actor, MaterialOrder $order, array $data): void
    {
        $this->expire($order->id);
        DB::transaction(function () use ($actor, $order, $data) {
            $o = MaterialOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $target = $data['status'];
            $seller = $o->seller_id === $actor->id;
            $buyer = $o->buyer_id === $actor->id;
            $next = ['awaiting_seller' => ['confirmed', 'cancelled'], 'confirmed' => ['ready_to_ship', 'cancelled'], 'ready_to_ship' => ['shipped'], 'shipped' => ['delivered']];
            abort_unless(in_array($target, $next[$o->status] ?? [], true), 409);
            abort_unless(($seller && in_array($target, ['confirmed', 'ready_to_ship', 'shipped', 'cancelled'])) || ($buyer && ($target === 'delivered' || ($target === 'cancelled' && ($o->status === 'awaiting_seller' || ($o->status === 'confirmed' && $o->quote_acceptance_required))))), 403);
            abort_if($target === 'ready_to_ship' && $o->quote_acceptance_required, 409, 'ابتدا خریدار باید مبلغ جدید را بپذیرد.');
            if ($target === 'confirmed') {
                $shipping = $data['shipping_cost'] ?? $o->shipping_cost;
                if ($shipping === null) {
                    throw ValidationException::withMessages(['shipping_cost' => 'فروشنده باید پیش از تأیید، هزینه حمل نهایی را مشخص کند.']);
                }
                $o->quote_acceptance_required = $o->shipping_cost === null || (int) $o->shipping_cost !== (int) $shipping;
                $o->quote_accepted_at = $o->quote_acceptance_required ? null : $o->quote_accepted_at;
                $o->reservation_expires_at = null;
                $o->shipping_cost = $shipping;
                $o->total = $o->subtotal + (int) $shipping;
            }
            if ($target === 'cancelled') {
                foreach ($o->items as $item) {
                    MaterialProduct::whereKey($item->material_product_id)->increment('stock', $item->quantity);
                }
            }
            $o->status = $target;
            $o->save();
            $o->events()->create(['user_id' => $actor->id, 'status' => $target, 'note' => $data['note'] ?? null]);
            app(AuditService::class)->record('order.status_changed', $o, ['status' => $target]);
            $other = User::findOrFail($buyer ? $o->seller_id : $o->buyer_id);
            app(NoticeService::class)->send($other, 'وضعیت سفارش تغییر کرد', Format::status($target), '/orders/'.$o->id);
        });
    }

    public function acceptQuote(User $actor, MaterialOrder $order, int $expectedTotal): void
    {
        DB::transaction(function () use ($actor, $order, $expectedTotal) {
            $o = MaterialOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($o->buyer_id === $actor->id, 403);
            abort_unless($o->status === 'confirmed' && $o->total !== null && (int) $o->total === $expectedTotal, 409);
            if (! $o->quote_acceptance_required) {
                return;
            }
            $o->update(['quote_acceptance_required' => false, 'quote_accepted_at' => now()]);
            $o->events()->create(['user_id' => $actor->id, 'status' => 'confirmed', 'note' => 'خریدار مبلغ نهایی جدید سفارش را پذیرفت؛ این رویداد پرداخت نیست.']);
            app(AuditService::class)->record('order.quote_accepted', $o, ['total' => $o->total]);
            app(NoticeService::class)->send($o->seller, 'مبلغ سفارش پذیرفته شد', 'خریدار مبلغ نهایی جدید را پذیرفت.', '/orders/'.$o->id);
        });
    }

    public function expire(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $o = MaterialOrder::whereKey($id)->lockForUpdate()->first();
            if (! $o || $o->status !== 'awaiting_seller' || ! $o->reservation_expires_at || $o->reservation_expires_at->isFuture()) {
                return false;
            }
            foreach ($o->items()->orderBy('material_product_id')->get() as $item) {
                MaterialProduct::whereKey($item->material_product_id)->increment('stock', $item->quantity);
            }
            $o->update(['status' => 'cancelled']);
            $o->events()->create(['user_id' => null, 'status' => 'cancelled', 'note' => 'لغو خودکار: مهلت پاسخ فروشنده تمام شد و موجودی آزاد شد.']);
            app(AuditService::class)->record('order.reservation_expired', $o);
            foreach ([$o->buyer, $o->seller] as $u) {
                app(NoticeService::class)->send($u, 'پایان مهلت سفارش', 'سفارش بدون پاسخ فروشنده لغو شد و موجودی آزاد شد.', '/orders/'.$o->id);
            }

            return true;
        }, 3);
    }
}
