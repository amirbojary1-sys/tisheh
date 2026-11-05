<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DemoPaymentAttempt;
use App\Models\MaterialOrder;
use App\Models\OtpChallenge;
use App\Models\Role;
use App\Models\User;
use App\Services\Integrations\MellatGateway;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DemoIntegrationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config(['integrations.demo_enabled' => true, 'integrations.otp_demo_enabled' => true, 'integrations.otp_demo_mobiles' => ['09000000003']]);
    }

    private function user(string $role = 'worker', ?string $mobile = null): User
    {
        $u = new User(['name' => 'آزمایش', 'last_name' => 'دمو', 'mobile' => $mobile ?? '09'.str_pad((string) random_int(1, 999999999), 9, '0', STR_PAD_LEFT), 'password' => 'TestPassword2026']);
        $u->role_id = Role::where('name', $role)->sole()->id;
        $u->save();
        $u->profile()->create(['city' => 'تهران']);

        return $u;
    }

    private function issue(): array
    {
        $u = $this->user('worker', '09000000003');
        $this->post('/login/otp', ['mobile' => $u->mobile])->assertRedirect('/login/otp/code');

        return [$u, OtpChallenge::first(), session('demo_otp_code'), session('otp_browser_token')];
    }

    private function order(User $buyer, array $extra = []): MaterialOrder
    {
        return MaterialOrder::create(['reference' => (string) Str::uuid(), 'idempotency_key' => (string) Str::uuid(), 'buyer_id' => $buyer->id, 'seller_id' => $this->user('seller')->id, 'status' => 'confirmed', 'subtotal' => 12000000, 'shipping_cost' => 5000000, 'total' => 17000000, 'delivery_address' => 'آدرس آزمایشی و خصوصی سفارش', 'payment_method' => 'direct', 'payment_status' => 'unverified_external', ...$extra]);
    }

    private function start(User $buyer, MaterialOrder $o): DemoPaymentAttempt
    {
        $this->actingAs($buyer)->post('/orders/'.$o->id.'/demo-payment')->assertRedirect();

        return DemoPaymentAttempt::latest()->firstOrFail();
    }

    public function test_four_digit_otp_form_and_password_login_coexist(): void
    {
        $this->get('/login')->assertOk()->assertSee('کد چهاررقمی');
        $this->get('/login/otp')->assertOk()->assertSee('09000000003');
    }

    public function test_demo_otp_is_hashed_expiring_and_consumed_without_real_verification(): void
    {
        [$u,$c,$code] = $this->issue();
        $this->assertMatchesRegularExpression('/^[0-9]{4}$/', $code);
        $this->assertNotEquals($code, $c->code_hash);
        $this->assertTrue(Hash::check($code, $c->code_hash));
        $this->get('/login/otp/code')->assertSee($code);
        $this->post('/login/otp/code', ['code' => $code])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($u);
        $this->assertNotNull($c->fresh()->consumed_at);
        $this->assertNull($u->fresh()->mobile_verified_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.demo_otp_consumed']);
    }

    public function test_demo_otp_can_be_enabled_without_demo_payments(): void
    {
        config(['integrations.demo_enabled' => false, 'integrations.otp_demo_enabled' => true]);
        [$u, $challenge, $code] = $this->issue();

        $this->assertMatchesRegularExpression('/^[0-9]{4}$/', $code);
        $this->post('/login/otp/code', ['code' => $code])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($u);

        $order = $this->order($u);
        $this->actingAs($u)->post('/orders/'.$order->id.'/demo-payment')->assertStatus(503);
    }

    public function test_wrong_otp_attempts_commit_instead_of_rolling_back(): void
    {
        [$u,$c,$code] = $this->issue();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login/otp/code', ['code' => '0000'])->assertSessionHasErrors('code');
        }$this->assertEquals(5, $c->fresh()->attempts);
        $this->post('/login/otp/code', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_expired_otp_does_not_login(): void
    {
        [$u,$c,$code] = $this->issue();
        $this->travel(121)->seconds();
        $this->post('/login/otp/code', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_otp_accepts_persian_digits_and_rejects_wrong_length(): void
    {
        [$u,$c,$code] = $this->issue();
        $this->post('/login/otp/code', ['code' => '123'])->assertSessionHasErrors('code');
        $persian = strtr($code, array_combine(str_split('0123456789'), ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹']));
        $this->post('/login/otp/code', ['code' => $persian])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($u);
    }

    public function test_otp_is_bound_to_browser_nonce(): void
    {
        [$u,$c,$code] = $this->issue();
        $this->withSession(['otp_browser_token' => 'different-browser'])->post('/login/otp/code', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_consumed_otp_cannot_be_replayed(): void
    {
        [$u,$c,$code,$token] = $this->issue();
        $this->post('/login/otp/code', ['code' => $code]);
        $this->post('/logout');
        $this->withSession(['otp_challenge_id' => $c->id, 'otp_browser_token' => $token])->post('/login/otp/code', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_displayed_demo_otp_cannot_access_admin_accounts(): void
    {
        $u = $this->user('admin', '09000000003');
        $this->post('/login/otp', ['mobile' => $u->mobile])->assertRedirect('/login/otp/code')->assertSessionMissing('demo_otp_code');
        $this->assertNull(OtpChallenge::first()->user_id);
    }

    public function test_unlisted_users_receive_no_demo_login_code(): void
    {
        $u = $this->user('worker', '09123456789');
        $this->post('/login/otp', ['mobile' => $u->mobile])->assertSessionMissing('demo_otp_code');
        $this->assertNull(OtpChallenge::first()->user_id);
    }

    public function test_otp_send_rate_limit_applies(): void
    {
        $u = $this->user('worker', '09000000003');
        $this->post('/login/otp', ['mobile' => $u->mobile])->assertRedirect();
        $this->post('/login/otp', ['mobile' => $u->mobile])->assertStatus(429);
    }

    public function test_demo_can_be_disabled_even_in_local_environment(): void
    {
        config(['integrations.demo_enabled' => false, 'integrations.otp_demo_enabled' => false]);
        $this->post('/login/otp', ['mobile' => '09000000003'])->assertStatus(503);
        $u = $this->user();
        $o = $this->order($u);
        $this->actingAs($u)->post('/orders/'.$o->id.'/demo-payment')->assertStatus(503);
    }

    public function test_demo_is_forcibly_disabled_in_production(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        app()->detectEnvironment(fn () => 'production');
        config(['integrations.demo_enabled' => true, 'integrations.otp_demo_enabled' => true]);
        $this->post('/login/otp', ['mobile' => '09000000003'])->assertStatus(503);
        $u = $this->user();
        $o = $this->order($u);
        $this->actingAs($u)->post('/orders/'.$o->id.'/demo-payment')->assertStatus(503);
    }

    public function test_gateway_success_is_demo_and_never_marks_order_paid(): void
    {
        $u = $this->user();
        $o = $this->order($u);
        $p = $this->start($u, $o);
        $this->get('/demo/payments/'.$p->id)->assertOk()->assertSee('درگاه پرداخت آزمایشی');
        $this->post('/demo/payments/'.$p->id, ['outcome' => 'success', 'amount' => 1])->assertRedirect();
        $this->assertEquals('demo_succeeded', $p->fresh()->status);
        $this->assertEquals(17000000, $p->fresh()->amount);
        $this->assertTrue($p->fresh()->is_demo);
        $this->assertEquals('unverified_external', $o->fresh()->payment_status);
        $this->assertEquals('confirmed', $o->fresh()->status);
        $this->get('/demo/payments/'.$p->id)->assertSee('شبیه‌سازی موفق بود')->assertDontSee('name="card_number"', false);
    }

    public function test_duplicate_start_and_completion_are_idempotent(): void
    {
        $u = $this->user();
        $o = $this->order($u);
        $p = $this->start($u, $o);
        $this->post('/orders/'.$o->id.'/demo-payment')->assertRedirect('/demo/payments/'.$p->id);
        $this->post('/demo/payments/'.$p->id, ['outcome' => 'success']);
        $this->post('/demo/payments/'.$p->id, ['outcome' => 'failure'])->assertRedirect();
        $this->assertEquals('demo_succeeded', $p->fresh()->status);
        $this->assertDatabaseCount('demo_payment_attempts', 1);
        $this->assertEquals(1, DB::table('audit_logs')->where('action', 'payment.demo_finished')->count());
    }

    public function test_failure_and_cancel_are_separate_attempts(): void
    {
        $u = $this->user();
        $o = $this->order($u);
        $p = $this->start($u, $o);
        $this->post('/demo/payments/'.$p->id, ['outcome' => 'failure'])->assertRedirect();
        $this->assertEquals('demo_failed', $p->fresh()->status);
        $this->post('/orders/'.$o->id.'/demo-payment')->assertRedirect();
        $next = DemoPaymentAttempt::where('status', 'demo_pending')->sole();
        $this->assertNotEquals($p->id, $next->id);
        $this->post('/demo/payments/'.$next->id, ['outcome' => 'cancel']);
        $this->assertEquals('demo_cancelled', $next->fresh()->status);
        $this->assertEquals('unverified_external', $o->fresh()->payment_status);
    }

    public function test_other_users_cannot_start_view_or_finish_payment_demo(): void
    {
        $u = $this->user();
        $o = $this->order($u);
        $p = $this->start($u, $o);
        $this->actingAs($this->user())->post('/orders/'.$o->id.'/demo-payment')->assertForbidden();
        $this->get('/demo/payments/'.$p->id)->assertForbidden();
        $this->post('/demo/payments/'.$p->id, ['outcome' => 'success'])->assertForbidden();
    }

    public function test_unknown_shipping_or_unconfirmed_order_prevents_demo_checkout(): void
    {
        $u = $this->user();
        $o = $this->order($u, ['total' => null, 'shipping_cost' => null, 'status' => 'awaiting_seller']);
        $this->actingAs($u)->post('/orders/'.$o->id.'/demo-payment')->assertStatus(409);
    }

    public function test_expired_attempt_and_changed_amount_are_rejected(): void
    {
        $u = $this->user();
        $o = $this->order($u);
        $p = $this->start($u, $o);
        $p->update(['expires_at' => now()->subSecond()]);
        $this->post('/demo/payments/'.$p->id, ['outcome' => 'success'])->assertStatus(409);
        $p->update(['expires_at' => now()->addMinutes(5)]);
        $o->update(['total' => 18000000]);
        $this->post('/demo/payments/'.$p->id, ['outcome' => 'success'])->assertStatus(409);
        $this->assertEquals('demo_pending', $p->fresh()->status);
    }

    public function test_cancelled_order_cannot_finish_demo_payment(): void
    {
        $u = $this->user();
        $o = $this->order($u);
        $p = $this->start($u, $o);
        $o->update(['status' => 'cancelled']);
        $this->post('/demo/payments/'.$p->id, ['outcome' => 'success'])->assertStatus(409);
    }

    public function test_admin_can_monitor_but_not_execute_buyer_demo(): void
    {
        $u = $this->user();
        $o = $this->order($u);
        $p = $this->start($u, $o);
        $this->actingAs($this->user('admin'))->get('/admin/demo-payments')->assertOk()->assertSee($p->provider_reference);
        $this->post('/demo/payments/'.$p->id, ['outcome' => 'success'])->assertForbidden();
        $this->actingAs($u)->get('/admin/demo-payments')->assertForbidden();
    }

    public function test_real_gateway_stub_is_not_accidentally_usable(): void
    {
        $this->expectException(\LogicException::class);
        app(MellatGateway::class)->create(1000000, 'IRR', 'test-order');
    }

    public function test_account_suspended_after_otp_issue_cannot_login(): void
    {
        [$u,$c,$code] = $this->issue();
        $u->forceFill(['status' => 'suspended'])->save();
        $this->post('/login/otp/code', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_demo_service_does_not_consume_non_demo_challenge(): void
    {
        [$u,$c,$code] = $this->issue();
        $c->update(['is_demo' => false]);
        $this->post('/login/otp/code', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_invalid_outcome_cannot_finish_attempt(): void
    {
        $u = $this->user();
        $p = $this->start($u, $this->order($u));
        $this->post('/demo/payments/'.$p->id, ['outcome' => 'paid'])->assertSessionHasErrors('outcome');
        $this->assertEquals('demo_pending', $p->fresh()->status);
    }

    public function test_demo_payment_audit_targets_order_with_attempt_uuid_metadata(): void
    {
        $u = $this->user();
        $o = $this->order($u);
        $p = $this->start($u, $o);
        $log = AuditLog::where('action', 'payment.demo_started')->sole();
        $this->assertEquals($o->id, $log->subject_id);
        $this->assertEquals(MaterialOrder::class, $log->subject_type);
        $this->assertEquals($p->id, $log->metadata['demo_attempt_id']);
    }
}
