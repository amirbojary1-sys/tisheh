<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CartCheckout;
use App\Models\Conversation;
use App\Models\JobCategory;
use App\Models\JobRequest;
use App\Models\MaterialCategory;
use App\Models\MaterialOrder;
use App\Models\MaterialProduct;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use App\Support\Format;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TishehTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function account(string $role = 'worker'): User
    {
        $u = new User(['name' => 'نام', 'last_name' => 'آزمایشی', 'mobile' => '09'.str_pad((string) random_int(1, 999999999), 9, '0', STR_PAD_LEFT), 'password' => 'SecurePass2026']);
        $u->role_id = Role::where('name', $role)->sole()->id;
        $u->save();
        $u->profile()->create(['city' => 'تهران']);
        if ($role === 'seller') {
            $u->location()->create(['name' => 'فروشگاه آزمایشی', 'city' => 'تهران', 'address' => 'آدرس نمونه']);
        }

        return $u;
    }

    private function jobData(): array
    {
        return ['title' => 'برق‌کار پروژه آزمایشی', 'category_id' => JobCategory::first()->id, 'description' => str_repeat('توضیحات معتبر برای پروژه آزمایشی. ', 3), 'city' => 'تهران', 'address' => 'آدرس خصوصی پروژه', 'workers_count' => 2, 'start_date' => now()->addDays(2)->format('Y-m-d'), 'duration_days' => 20, 'wage' => 18000000, 'payment_type' => 'daily', 'status' => 'pending'];
    }

    private function job(?User $owner = null, string $status = 'published'): JobRequest
    {
        $d = $this->jobData();
        $d['job_category_id'] = $d['category_id'];
        unset($d['category_id']);

        return JobRequest::create([...$d, 'user_id' => ($owner ?? $this->account('employer'))->id, 'status' => $status]);
    }

    private function productData(): array
    {
        return ['name' => 'سیمان آزمایشی', 'category_id' => MaterialCategory::first()->id, 'brand' => 'برند نمونه', 'specifications' => 'مشخصات فنی واقعی برای آزمون ثبت آگهی مصالح ساختمانی', 'price' => 1200000, 'unit' => 'bag', 'stock' => 100, 'minimum_order' => 10, 'shipping_cost' => 5000000, 'city' => 'تهران', 'status' => 'pending'];
    }

    private function product(?User $owner = null, string $status = 'published', array $extra = []): MaterialProduct
    {
        $d = $this->productData();
        $d['material_category_id'] = $d['category_id'];
        unset($d['category_id']);

        return MaterialProduct::create([...$d, 'user_id' => ($owner ?? $this->account('seller'))->id, 'status' => $status, ...$extra]);
    }

    public function test_home_is_live_and_uses_csp(): void
    {
        $this->get('/')->assertOk()->assertSee('محکم‌تر')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Content-Security-Policy');
    }

    public function test_registration_normalizes_persian_mobile_and_hashes_password(): void
    {
        $this->post('/register', ['name' => 'رضا', 'last_name' => 'نمونه', 'mobile' => '۰۹۱۲۳۴۵۶۷۸۹', 'password' => 'SecurePass2026', 'password_confirmation' => 'SecurePass2026', 'role' => 'worker', 'city' => 'تهران', 'specialty' => 'بنایی', 'terms' => 1])->assertRedirect('/dashboard');
        $u = User::where('mobile', '09123456789')->sole();
        $this->assertTrue(Hash::check('SecurePass2026', $u->password));
        $this->assertNull($u->mobile_verified_at);
        $this->assertEquals('بنایی', $u->profile->specialty);
        $this->assertAuthenticatedAs($u);
    }

    public function test_public_cannot_register_as_admin(): void
    {
        $this->post('/register', ['name' => 'نام', 'last_name' => 'نام', 'mobile' => '09123456789', 'password' => 'SecurePass2026', 'password_confirmation' => 'SecurePass2026', 'role' => 'admin', 'city' => 'تهران', 'terms' => 1])->assertSessionHasErrors('role');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_seller_requires_business_name_and_address(): void
    {
        $this->post('/register', ['name' => 'نام', 'last_name' => 'نام', 'mobile' => '09123456789', 'password' => 'SecurePass2026', 'password_confirmation' => 'SecurePass2026', 'role' => 'seller', 'city' => 'تهران', 'terms' => 1])->assertSessionHasErrors(['business_name', 'address']);
    }

    public function test_duplicate_mobile_and_weak_password_are_rejected(): void
    {
        $u = $this->account();
        $this->post('/register', ['name' => 'نام', 'last_name' => 'نام', 'mobile' => $u->mobile, 'password' => '123', 'password_confirmation' => '123', 'role' => 'worker', 'specialty' => 'بنایی', 'city' => 'تهران', 'terms' => 1])->assertSessionHasErrors(['mobile', 'password']);
    }

    public function test_failed_form_preserves_name_but_not_password(): void
    {
        $this->from('/register')->post('/register', ['name' => 'نام ماندگار', 'password' => 'secret'])->assertRedirect('/register')->assertSessionHasInput('name', 'نام ماندگار')->assertSessionMissing('_old_input.password');
    }

    public function test_valid_login_and_post_logout(): void
    {
        $u = $this->account();
        $this->post('/login', ['mobile' => $u->mobile, 'password' => 'SecurePass2026'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($u);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_admin_login_redirects_to_admin_panel(): void
    {
        $admin = $this->account('admin');

        $this->post('/login', ['mobile' => $admin->mobile, 'password' => 'SecurePass2026'])
            ->assertRedirect(route('admin'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_wrong_password_never_authenticates(): void
    {
        $u = $this->account();
        $this->post('/login', ['mobile' => $u->mobile, 'password' => 'invalid'])->assertSessionHasErrors('mobile');
        $this->assertGuest();
    }

    public function test_suspended_account_cannot_login(): void
    {
        $u = $this->account();
        $u->forceFill(['status' => 'suspended'])->save();
        $this->post('/login', ['mobile' => $u->mobile, 'password' => 'SecurePass2026'])->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['mobile' => '09123456789', 'password' => 'invalid']);
        }$this->post('/login', ['mobile' => '09123456789', 'password' => 'invalid'])->assertStatus(429);
    }

    public function test_guest_redirected_from_dashboard_and_mutations(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->post('/jobs', $this->jobData())->assertRedirect('/login');
    }

    public function test_role_permissions_are_server_enforced(): void
    {
        $worker = $this->account();
        $this->actingAs($worker)->post('/jobs', $this->jobData())->assertForbidden();
        $this->actingAs($worker)->post('/materials', $this->productData())->assertForbidden();
        $this->actingAs($worker)->get('/admin')->assertForbidden();
    }

    public function test_employer_can_save_draft_and_submit_for_moderation(): void
    {
        $u = $this->account('employer');
        $this->actingAs($u)->post('/jobs', [...$this->jobData(), 'status' => 'draft'])->assertRedirect();
        $this->assertDatabaseHas('job_requests', ['user_id' => $u->id, 'status' => 'draft']);
        $j = JobRequest::first();
        $this->actingAs($u)->put('/jobs/'.$j->id, $this->jobData())->assertRedirect();
        $this->assertEquals('pending', $j->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'job.saved', 'subject_id' => $j->id]);
    }

    public function test_client_cannot_bypass_moderation(): void
    {
        $this->actingAs($this->account('employer'))->post('/jobs', [...$this->jobData(), 'status' => 'published'])->assertSessionHasErrors('status');
    }

    public function test_invalid_job_fields_report_errors(): void
    {
        $this->actingAs($this->account('employer'))->post('/jobs', [...$this->jobData(), 'workers_count' => 0, 'description' => 'short', 'start_date' => '2020-01-01'])->assertSessionHasErrors(['workers_count', 'description', 'start_date']);
    }

    public function test_job_private_address_never_in_public_response(): void
    {
        $j = $this->job();
        $this->get('/jobs/'.$j->id)->assertOk()->assertDontSee($j->address)->assertDontSee($j->user->mobile);
    }

    public function test_pending_job_is_not_public(): void
    {
        $j = $this->job(status: 'pending');
        $this->get('/jobs/'.$j->id)->assertForbidden();
        $this->get('/jobs')->assertDontSee($j->title);
    }

    public function test_nonowner_cannot_edit_or_close_job(): void
    {
        $j = $this->job();
        $u = $this->account('employer');
        $this->actingAs($u)->put('/jobs/'.$j->id, $this->jobData())->assertForbidden();
        $this->actingAs($u)->post('/jobs/'.$j->id.'/close', ['status' => 'completed'])->assertForbidden();
        $this->actingAs($u)->delete('/jobs/'.$j->id)->assertForbidden();
    }

    public function test_owner_can_delete_job_without_user_history(): void
    {
        $owner = $this->account('employer');
        $j = $this->job($owner, 'draft');

        $this->actingAs($owner)->delete('/jobs/'.$j->id)->assertRedirect('/jobs');

        $this->assertDatabaseMissing('job_requests', ['id' => $j->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'job.deleted', 'subject_id' => $j->id]);
    }

    public function test_job_with_applications_cannot_be_deleted(): void
    {
        $owner = $this->account('employer');
        $j = $this->job($owner);
        $j->applications()->create(['user_id' => $this->account('worker')->id, 'message' => 'درخواست نمونه']);

        $this->actingAs($owner)->from('/jobs/'.$j->id)->delete('/jobs/'.$j->id)->assertSessionHasErrors('job');

        $this->assertDatabaseHas('job_requests', ['id' => $j->id]);
        $this->assertDatabaseCount('job_applications', 1);
    }

    public function test_job_with_conversation_or_report_cannot_be_deleted(): void
    {
        $owner = $this->account('employer');
        $j = $this->job($owner, 'draft');
        $recipient = $this->account('worker');
        Conversation::create([
            'initiator_id' => $recipient->id,
            'recipient_id' => $owner->id,
            'context_type' => 'job',
            'context_id' => $j->id,
            'title' => $j->title,
        ]);

        $this->actingAs($owner)->from('/jobs/'.$j->id)->delete('/jobs/'.$j->id)->assertSessionHasErrors('job');
        $this->assertDatabaseHas('job_requests', ['id' => $j->id]);
    }

    public function test_job_with_report_cannot_be_deleted(): void
    {
        $owner = $this->account('employer');
        $j = $this->job($owner, 'draft');
        Report::create([
            'user_id' => $this->account('worker')->id,
            'subject_type' => 'job',
            'subject_id' => $j->id,
            'reason' => 'spam',
            'description' => 'گزارش نمونه',
        ]);

        $this->actingAs($owner)->from('/jobs/'.$j->id)->delete('/jobs/'.$j->id)->assertSessionHasErrors('job');
        $this->assertDatabaseHas('job_requests', ['id' => $j->id]);
    }

    public function test_cart_persists_quantities_and_is_scoped_to_its_owner(): void
    {
        $product = $this->product();
        $buyer = $this->account();
        $other = $this->account();

        $this->actingAs($buyer)->post('/cart/items/'.$product->id, ['quantity' => 10])->assertRedirect('/cart');
        $this->post('/cart/items/'.$product->id, ['quantity' => 5])->assertRedirect('/cart');
        $this->assertDatabaseHas('shopping_cart_items', ['material_product_id' => $product->id, 'quantity' => 15]);
        $this->get('/cart')->assertOk()->assertSee($product->name);

        $this->actingAs($other)->get('/cart')->assertOk()->assertDontSee($product->name);
        $this->patch('/cart/items/'.$product->id, ['quantity' => 20])->assertNotFound();
    }

    public function test_checkout_splits_cart_by_seller_and_reserves_all_items_atomically(): void
    {
        $sellerOne = $this->account('seller');
        $sellerTwo = $this->account('seller');
        $buyer = $this->account();
        $first = $this->product($sellerOne);
        $second = $this->product($sellerOne);
        $third = $this->product($sellerTwo);

        $this->actingAs($buyer);
        foreach ([$first, $second, $third] as $product) {
            $this->post('/cart/items/'.$product->id, ['quantity' => 10])->assertRedirect('/cart');
        }

        $this->post('/cart/checkout', [
            'delivery_address' => 'تهران، خیابان نمونه، پلاک ۱۰',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect('/orders');

        $orders = MaterialOrder::with('items')->where('buyer_id', $buyer->id)->orderBy('seller_id')->get();
        $this->assertCount(2, $orders);
        $firstSellerOrder = $orders->firstWhere('seller_id', $sellerOne->id);
        $secondSellerOrder = $orders->firstWhere('seller_id', $sellerTwo->id);
        $this->assertCount(2, $firstSellerOrder->items);
        $this->assertNull($firstSellerOrder->shipping_cost);
        $this->assertNull($firstSellerOrder->total);
        $this->assertCount(1, $secondSellerOrder->items);
        $this->assertNotNull($secondSellerOrder->shipping_cost);
        $this->assertSame(90, $first->fresh()->stock);
        $this->assertSame(90, $second->fresh()->stock);
        $this->assertSame(90, $third->fresh()->stock);
        $this->assertDatabaseCount('shopping_cart_items', 0);
        $this->assertDatabaseCount('cart_checkout_orders', 2);
    }

    public function test_checkout_replay_returns_existing_orders_without_reserving_stock_twice(): void
    {
        $buyer = $this->account();
        $product = $this->product();
        $key = (string) Str::uuid();
        $checkout = ['delivery_address' => 'تهران، خیابان نمونه، پلاک ۱۰', 'idempotency_key' => $key];

        $this->actingAs($buyer)->post('/cart/items/'.$product->id, ['quantity' => 10])->assertRedirect('/cart');
        $this->post('/cart/checkout', $checkout)->assertRedirect();
        $this->post('/cart/checkout', $checkout)->assertRedirect();

        $this->assertSame(90, $product->fresh()->stock);
        $this->assertDatabaseCount('material_orders', 1);
        $this->assertDatabaseCount('cart_checkouts', 1);
        $this->assertCount(1, CartCheckout::with('orders')->where('user_id', $buyer->id)->firstOrFail()->orders);
    }

    public function test_checkout_rejects_stale_minimum_order_without_partial_orders_or_stock_changes(): void
    {
        $sellerOne = $this->account('seller');
        $sellerTwo = $this->account('seller');
        $buyer = $this->account();
        $available = $this->product($sellerOne);
        $belowMinimum = $this->product($sellerTwo, extra: ['stock' => 5]);

        $this->actingAs($buyer);
        $this->post('/cart/items/'.$available->id, ['quantity' => 10])->assertRedirect('/cart');
        $this->post('/cart/items/'.$belowMinimum->id, ['quantity' => 1])->assertRedirect('/cart');
        $this->from('/cart')->post('/cart/checkout', [
            'delivery_address' => 'تهران، خیابان نمونه، پلاک ۱۰',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('material_orders', 0);
        $this->assertDatabaseCount('shopping_cart_items', 2);
        $this->assertSame(100, $available->fresh()->stock);
        $this->assertSame(5, $belowMinimum->fresh()->stock);
    }

    public function test_worker_can_apply_exactly_once(): void
    {
        $j = $this->job();
        $u = $this->account();
        $this->actingAs($u)->post('/jobs/'.$j->id.'/apply', ['message' => 'با سابقه کاری مرتبط آماده همکاری هستم.'])->assertRedirect();
        $this->actingAs($u)->post('/jobs/'.$j->id.'/apply', ['message' => 'دوباره اعلام آمادگی می‌کنم.'])->assertSessionHasErrors('message');
        $this->assertDatabaseCount('job_applications', 1);
    }

    public function test_employer_cannot_apply_as_worker(): void
    {
        $j = $this->job();
        $this->actingAs($this->account('employer'))->post('/jobs/'.$j->id.'/apply', ['message' => 'آماده همکاری هستم.'])->assertForbidden();
    }

    public function test_employer_reviews_only_own_applicants(): void
    {
        $owner = $this->account('employer');
        $j = $this->job($owner);
        $worker = $this->account();
        $a = $j->applications()->create(['user_id' => $worker->id, 'message' => 'درخواست نمونه']);
        $this->actingAs($this->account('employer'))->patch('/applications/'.$a->id, ['status' => 'accepted'])->assertForbidden();
        $this->actingAs($owner)->patch('/applications/'.$a->id, ['status' => 'accepted'])->assertRedirect();
        $this->assertEquals('accepted', $a->fresh()->status);
    }

    public function test_seller_can_submit_product_but_cannot_publish_it(): void
    {
        $seller = $this->account('seller');
        $this->actingAs($seller)->post('/materials', $this->productData())->assertRedirect();
        $this->assertDatabaseHas('material_products', ['user_id' => $seller->id, 'status' => 'pending']);
        $this->actingAs($seller)->post('/materials', [...$this->productData(), 'status' => 'published'])->assertSessionHasErrors('status');
    }

    public function test_untrusted_svg_upload_is_rejected(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->createWithContent('evil.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $this->actingAs($this->account('seller'))->post('/materials', [...$this->productData(), 'image' => $file])->assertSessionHasErrors('image');
        $this->assertDatabaseCount('material_products', 0);
    }

    public function test_nonowner_cannot_update_product(): void
    {
        $p = $this->product();
        $this->actingAs($this->account('seller'))->put('/materials/'.$p->id, $this->productData())->assertForbidden();
    }

    public function test_product_comparison_rejects_hidden_product(): void
    {
        $a = $this->product();
        $b = $this->product(status: 'pending');
        $this->get('/compare?ids[]='.$a->id.'&ids[]='.$b->id)->assertNotFound();
    }

    public function test_comparison_displays_unknown_shipping_as_unknown(): void
    {
        $a = $this->product(extra: ['shipping_cost' => null]);
        $b = $this->product();
        $this->get('/compare?ids[]='.$a->id.'&ids[]='.$b->id)->assertOk()->assertSee('نامشخص')->assertSee('نیازمند استعلام');
    }

    public function test_search_filters_match_database_not_static_cards(): void
    {
        $j = $this->job();
        $this->get('/jobs?q='.urlencode('برق‌کار'))->assertOk()->assertSee($j->title);
        $this->get('/jobs?city='.urlencode('تبریز'))->assertDontSee($j->title);
        $p = $this->product();
        $this->get('/materials?brand='.urlencode('برند نمونه'))->assertSee($p->name);
        $this->get('/materials?brand=nonexistent')->assertDontSee($p->name);
    }

    public function test_xss_is_escaped_in_public_listing(): void
    {
        $j = $this->job();
        $j->update(['description' => '<script>alert("xss")</script>']);
        $this->get('/jobs/'.$j->id)->assertOk()->assertDontSee('<script>alert("xss")</script>', false)->assertSee('&lt;script&gt;', false);
    }

    public function test_admin_moderation_and_suspension_are_audited(): void
    {
        $admin = $this->account('admin');
        $j = $this->job(status: 'pending');
        $this->actingAs($admin)->patch('/admin/moderate/job/'.$j->id, ['status' => 'published'])->assertRedirect();
        $this->assertEquals('published', $j->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'job.moderated', 'user_id' => $admin->id]);
        $this->actingAs($admin)->patch('/admin/users/'.$j->user_id, ['status' => 'suspended'])->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.status_changed']);
    }

    public function test_admin_cannot_suspend_self_and_rejection_needs_reason(): void
    {
        $admin = $this->account('admin');
        $j = $this->job(status: 'pending');
        $this->actingAs($admin)->patch('/admin/users/'.$admin->id, ['status' => 'suspended'])->assertForbidden();
        $this->actingAs($admin)->patch('/admin/moderate/job/'.$j->id, ['status' => 'rejected'])->assertSessionHasErrors('moderation_note');
    }

    public function test_profile_update_cannot_change_role_or_verify_phone(): void
    {
        $u = $this->account();
        $this->actingAs($u)->put('/profile', ['name' => 'نام جدید', 'last_name' => 'نمونه', 'city' => 'کرج', 'role_id' => Role::where('name', 'admin')->sole()->id, 'mobile_verified_at' => now()])->assertRedirect();
        $this->assertTrue($u->fresh()->hasRole('worker'));
        $this->assertNull($u->fresh()->mobile_verified_at);
    }

    public function test_dashboards_render_for_all_roles(): void
    {
        foreach (['worker', 'seller', 'employer', 'admin'] as $role) {
            $u = $this->account($role);
            $this->actingAs($u)->get('/dashboard')->assertOk();
        } $this->get('/admin')->assertOk();
    }

    public function test_audit_log_model_is_append_only(): void
    {
        $u = $this->account();
        app(AuditService::class)->record('test', $u);
        $this->expectException(\LogicException::class);
        AuditLog::first()->update(['action' => 'tampered']);
    }

    public function test_financial_services_are_not_enabled(): void
    {
        $this->assertFalse(config('tisheh.payments_enabled'));
        $this->assertFalse(config('tisheh.wallet_enabled'));
        $this->get('/wallet')->assertNotFound();
    }

    public function test_optional_wage_and_shipping_remain_null(): void
    {
        $e = $this->account('employer');
        $this->actingAs($e)->post('/jobs', [...$this->jobData(), 'wage' => ''])->assertRedirect();
        $this->assertNull(JobRequest::first()->wage);
        $seller = $this->account('seller');
        $this->actingAs($seller)->post('/materials', [...$this->productData(), 'shipping_cost' => ''])->assertRedirect();
        $this->assertNull(MaterialProduct::first()->shipping_cost);
    }

    public function test_zero_stock_is_a_valid_seller_update(): void
    {
        $seller = $this->account('seller');
        $p = $this->product($seller);
        $this->actingAs($seller)->put('/materials/'.$p->id, [...$this->productData(), 'stock' => 0, 'minimum_order' => 1])->assertRedirect();
        $this->assertEquals(0, $p->fresh()->stock);
    }

    public function test_array_mobile_payload_is_validation_error_not_server_error(): void
    {
        $this->post('/login', ['mobile' => ['bad'], 'password' => 'invalid'])->assertSessionHasErrors('mobile');
    }

    public function test_valid_uploaded_image_is_reencoded_and_persisted(): void
    {
        Storage::fake('public');
        $seller = $this->account('seller');
        $file = UploadedFile::fake()->image('original.png', 100, 100);
        $this->actingAs($seller)->post('/materials', [...$this->productData(), 'image' => $file])->assertRedirect();
        $p = MaterialProduct::first();
        $path = $p->images()->first()->path;
        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertStringContainsString('WEBP', substr(Storage::disk('public')->get($path), 0, 20));
    }

    public function test_money_display_does_not_round_away_single_rial(): void
    {
        $this->assertSame('۱۰۰٫۱ تومان', Format::money(1001));
    }
}
