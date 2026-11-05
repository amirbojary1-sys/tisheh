<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\DemoPaymentAttempt;
use App\Models\MaterialOrder;
use App\Models\User;
use App\Support\DemoMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoPaymentService
{
    public function __construct(private PaymentGateway $gateway) {}

    public function start(User $actor, MaterialOrder $order): DemoPaymentAttempt
    {
        DemoMode::ensureEnabled();

        return DB::transaction(function () use ($actor, $order) {
            $o = MaterialOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($o->buyer_id === $actor->id, 403);
            abort_unless(! $o->quote_acceptance_required && in_array($o->status, ['confirmed', 'ready_to_ship'], true) && $o->total !== null && $o->total > 0, 409);
            $prior = DemoPaymentAttempt::where('material_order_id', $o->id)->where('user_id', $actor->id)->where('amount', $o->total)->where(fn ($q) => $q->where('status', 'demo_succeeded')->orWhere(fn ($q) => $q->where('status', 'demo_pending')->where('expires_at', '>', now())))->latest()->first();
            if ($prior) {
                return $prior;
            }
            $reference = $this->gateway->create((int) $o->total, $o->currency, $o->reference);
            $p = DemoPaymentAttempt::create(['id' => (string) Str::uuid(), 'material_order_id' => $o->id, 'user_id' => $actor->id, 'provider_reference' => $reference, 'amount' => $o->total, 'currency' => $o->currency, 'is_demo' => true, 'expires_at' => now()->addMinutes(config('integrations.payment_ttl_minutes'))]);
            app(AuditService::class)->record('payment.demo_started', $o, ['is_demo' => true, 'demo_attempt_id' => $p->id]);

            return $p;
        });
    }

    public function complete(User $actor, DemoPaymentAttempt $attempt, string $outcome): DemoPaymentAttempt
    {
        DemoMode::ensureEnabled();

        return DB::transaction(function () use ($actor, $attempt, $outcome) {
            // Same lock order as start: order first, attempt second.
            $o = MaterialOrder::whereKey($attempt->material_order_id)->lockForUpdate()->firstOrFail();
            $p = DemoPaymentAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            abort_unless($p->user_id === $actor->id && $o->buyer_id === $actor->id, 403);
            abort_unless($p->is_demo, 409);
            if ($p->status !== 'demo_pending') {
                return $p;
            }
            abort_unless($p->expires_at->isFuture() && ! $o->quote_acceptance_required && in_array($o->status, ['confirmed', 'ready_to_ship'], true) && (int) $p->amount === (int) $o->total && $p->currency === $o->currency, 409);
            $result = $this->gateway->verify($p->provider_reference, (int) $p->amount, $p->currency, ['outcome' => $outcome]);
            abort_unless($result->isDemo && $result->reference === $p->provider_reference, 409);
            $p->update(['status' => $result->status, 'finished_at' => now()]);
            app(AuditService::class)->record('payment.demo_finished', $o, ['status' => $p->status, 'is_demo' => true, 'demo_attempt_id' => $p->id]);
            // Intentionally NO update of real payment_status, ledger, revenue, wallet or commercial order state.
            app(NoticeService::class)->send($actor, 'نتیجه آزمایش درگاه', 'این نتیجه دمو است و پرداخت واقعی نیست.', '/demo/payments/'.$p->id);

            return $p;
        });
    }
}
