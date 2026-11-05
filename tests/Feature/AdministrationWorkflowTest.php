<?php

namespace Tests\Feature;

use App\Models\ContentPage;
use App\Models\Conversation;
use App\Models\JobCategory;
use App\Models\JobRequest;
use App\Models\MaterialCategory;
use App\Models\MaterialOrder;
use App\Models\MaterialProduct;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use App\Services\NoticeService;
use App\Support\Format;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdministrationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function user(string $role = 'worker'): User
    {
        $u = new User(['name' => 'آزمون', 'last_name' => 'گردش', 'mobile' => '09'.str_pad((string) random_int(1, 999999999), 9, '0', STR_PAD_LEFT), 'password' => 'SecurePass2026']);
        $u->role_id = Role::where('name', $role)->sole()->id;
        $u->save();
        $u->profile()->create(['city' => 'تهران']);
        if ($role === 'seller') {
            $u->location()->create(['name' => 'فروشگاه آزمایشی', 'city' => 'تهران', 'address' => 'آدرس نمونه فروشگاه']);
        }

        return $u;
    }

    private function product(?User $seller = null, array $extra = []): MaterialProduct
    {
        return MaterialProduct::create(['user_id' => ($seller ?? $this->user('seller'))->id, 'material_category_id' => MaterialCategory::first()->id, 'name' => 'سیمان تست', 'brand' => 'برند تست', 'specifications' => 'مشخصات فنی آزمایشی محصول', 'price' => 1200000, 'unit' => 'bag', 'stock' => 100, 'minimum_order' => 10, 'shipping_cost' => 5000000, 'city' => 'تهران', 'status' => 'published', ...$extra]);
    }

    private function job(?User $owner = null): JobRequest
    {
        return JobRequest::create(['user_id' => ($owner ?? $this->user('employer'))->id, 'job_category_id' => JobCategory::first()->id, 'title' => 'آگهی کار تست', 'description' => 'توضیحات کافی جهت بررسی گردش کار آزمایشی سامانه', 'city' => 'تهران', 'address' => 'آدرس خصوصی', 'workers_count' => 2, 'start_date' => now()->addDays(2), 'duration_days' => 20, 'payment_type' => 'daily', 'status' => 'published']);
    }

    private function orderData(): array
    {
        return ['quantity' => 10, 'delivery_address' => 'آدرس آزمایشی تحویل سفارش مصالح', 'idempotency_key' => (string) Str::uuid(), 'payment_method' => 'direct'];
    }

    private function order(User $buyer, MaterialProduct $p): MaterialOrder
    {
        $this->actingAs($buyer)->post('/materials/'.$p->id.'/order', $this->orderData())->assertRedirect();

        return MaterialOrder::latest('id')->firstOrFail();
    }

    public function test_all_admin_sections_render_with_real_data(): void
    {
        $admin = $this->user('admin');
        $this->product();
        $this->job();
        $this->actingAs($admin);
        foreach (['/admin', '/admin/users', '/admin/listings/job', '/admin/listings/product', '/admin/categories', '/admin/reports', '/admin/pages', '/admin/settings', '/admin/audit', '/admin/orders', '/admin/roles', '/admin/finance'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_nonadmin_cannot_open_any_admin_section(): void
    {
        $this->actingAs($this->user());
        foreach (['/admin/users', '/admin/settings', '/admin/pages', '/admin/audit', '/admin/finance', '/admin/orders'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_admin_user_filters_and_edit_are_real(): void
    {
        $u = $this->user('worker');
        $this->actingAs($this->user('admin'))->get('/admin/users?q='.$u->mobile)->assertSee($u->mobile);
        $this->patch('/admin/users/'.$u->id, ['name' => 'نام اصلاح‌شده', 'last_name' => 'نمونه', 'city' => 'کرج', 'status' => 'suspended', 'reason' => 'بررسی تخلف'])->assertRedirect();
        $this->assertEquals('suspended', $u->fresh()->status);
        $this->assertEquals('کرج', $u->profile->fresh()->city);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.status_changed']);
    }

    public function test_admin_cannot_assign_admin_or_forge_verification(): void
    {
        $u = $this->user();
        $this->actingAs($this->user('admin'))->patch('/admin/users/'.$u->id, ['status' => 'active', 'role_id' => Role::where('name', 'admin')->sole()->id, 'mobile_verified_at' => now()])->assertSessionHasErrors(['role_id', 'mobile_verified_at']);
        $this->assertTrue($u->fresh()->hasRole('worker'));
    }

    public function test_used_categories_cannot_be_deleted(): void
    {
        $p = $this->product();
        $this->actingAs($this->user('admin'))->delete('/admin/categories/product/'.$p->material_category_id)->assertSessionHasErrors('category');
        $this->assertDatabaseHas('material_categories', ['id' => $p->material_category_id]);
    }

    public function test_unused_categories_can_be_created_updated_deleted(): void
    {
        $this->actingAs($this->user('admin'))->post('/admin/categories/job', ['name' => 'تخصص تازه', 'slug' => 'new-specialty'])->assertRedirect();
        $c = JobCategory::where('slug', 'new-specialty')->sole();
        $this->put('/admin/categories/job/'.$c->id, ['name' => 'تخصص اصلاح‌شده', 'slug' => 'new-specialty'])->assertRedirect();
        $this->assertEquals('تخصص اصلاح‌شده', $c->fresh()->name);
        $this->delete('/admin/categories/job/'.$c->id)->assertRedirect();
        $this->assertDatabaseMissing('job_categories', ['id' => $c->id]);
    }

    public function test_moderation_generates_private_notification(): void
    {
        $p = $this->product(extra: ['status' => 'pending']);
        $this->actingAs($this->user('admin'))->patch('/admin/moderate/product/'.$p->id, ['status' => 'published'])->assertRedirect();
        $this->assertEquals('published', $p->fresh()->status);
        $this->assertEquals(1, $p->user->notifications()->count());
    }

    public function test_suspension_removes_public_listings(): void
    {
        $seller = $this->user('seller');
        $p = $this->product($seller);
        $seller->status = 'suspended';
        $seller->save();
        $this->get('/materials')->assertDontSee($p->name);
        $this->get('/materials/'.$p->id)->assertForbidden();
    }

    public function test_admin_can_remove_published_listing_with_reason(): void
    {
        $p = $this->product();
        $this->actingAs($this->user('admin'))->patch('/admin/listings/product/'.$p->id, ['status' => 'rejected', 'moderation_note' => 'رسیدگی به گزارش معتبر'])->assertRedirect();
        $this->assertEquals('rejected', $p->fresh()->status);
    }

    public function test_settings_persist_but_cannot_enable_money(): void
    {
        $this->actingAs($this->user('admin'))->put('/admin/settings', ['support_phone' => '02112345678', 'publication_fee_rial' => 1000000, 'subscription_fee_rial' => 5000000, 'announcement' => 'اطلاعیه آزمایشی'])->assertRedirect();
        $this->assertDatabaseHas('settings', ['key' => 'publication_fee_rial']);
        $this->put('/admin/settings', ['publication_fee_rial' => 0, 'subscription_fee_rial' => 0, 'payments_enabled' => true])->assertSessionHasErrors('payments_enabled');
        $this->assertFalse(config('tisheh.payments_enabled'));
    }

    public function test_admin_can_change_password_from_settings(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin)->get('/admin/settings')
            ->assertOk()
            ->assertSee('تغییر رمز عبور مدیر')
            ->assertSee('current_password')
            ->assertSee('password_confirmation');

        $this->put('/account/password', [
            'current_password' => 'SecurePass2026',
            'password' => 'ChangedAdmin2026',
            'password_confirmation' => 'ChangedAdmin2026',
        ])->assertRedirect('/login');

        $this->assertGuest();
        $this->assertTrue(Hash::check('ChangedAdmin2026', $admin->fresh()->password));
        $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => 'password.changed']);
    }

    public function test_content_is_editable_but_html_is_never_executed(): void
    {
        $this->actingAs($this->user('admin'))->post('/admin/pages', ['slug' => 'test-help', 'title' => 'راهنمای آزمون', 'body' => 'متن راهنما به همراه <script>alert(1)</script> برای تست امنیت', 'published' => 1])->assertRedirect();
        $this->get('/pages/test-help')->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_draft_content_is_not_public(): void
    {
        ContentPage::create(['slug' => 'private-draft', 'title' => 'پیش‌نویس', 'body' => 'متن محتوای منتشرنشده آزمایشی', 'published' => false]);
        $this->get('/pages/private-draft')->assertNotFound();
    }

    public function test_report_response_and_notification_are_owner_scoped(): void
    {
        $u = $this->user();
        $this->actingAs($u)->post('/reports', ['subject_type' => 'support', 'reason' => 'درخواست راهنمایی', 'description' => 'برای ثبت و پیگیری درخواست نیاز به راهنمایی دارم.'])->assertRedirect('/reports');
        $report = Report::first();
        $this->actingAs($this->user('admin'))->patch('/admin/reports/'.$report->id, ['status' => 'resolved', 'admin_response' => 'پاسخ پشتیبانی برای شما ثبت شد.'])->assertRedirect();
        $this->actingAs($u)->get('/reports')->assertSee('پاسخ پشتیبانی برای شما ثبت شد.');
        $this->actingAs($this->user())->get('/reports')->assertDontSee('پاسخ پشتیبانی برای شما ثبت شد.');
        $this->assertEquals(1, $u->notifications()->count());
    }

    public function test_report_cannot_reference_private_listing(): void
    {
        $p = $this->product(extra: ['status' => 'draft']);
        $this->actingAs($this->user())->post('/reports', ['subject_type' => 'product', 'subject_id' => $p->id, 'reason' => 'موضوع تست', 'description' => 'توضیحات کافی برای ثبت گزارش آزمایشی'])->assertForbidden();
    }

    public function test_notifications_cannot_be_marked_by_other_users(): void
    {
        $u = $this->user();
        app(NoticeService::class)->send($u, 'اعلان', 'متن', '/dashboard');
        $id = $u->notifications()->first()->id;
        $this->actingAs($this->user())->post('/notifications/'.$id.'/read')->assertNotFound();
        $this->actingAs($u)->post('/notifications/'.$id.'/read')->assertRedirect();
        $this->assertNotNull($u->notifications()->first()->read_at);
    }

    public function test_thread_is_unique_per_listing_and_initiator(): void
    {
        $p = $this->product();
        $u = $this->user();
        $this->actingAs($u)->post('/messages/start/product/'.$p->id)->assertRedirect();
        $this->post('/messages/start/product/'.$p->id)->assertRedirect();
        $this->assertDatabaseCount('conversations', 1);
    }

    public function test_only_conversation_participants_can_read_and_write(): void
    {
        $p = $this->product();
        $u = $this->user();
        $this->actingAs($u)->post('/messages/start/product/'.$p->id);
        $thread = Conversation::first();
        $this->actingAs($this->user())->get('/messages/'.$thread->id)->assertForbidden();
        $this->post('/messages/'.$thread->id, ['body' => 'متن پیام غیرمجاز'])->assertForbidden();
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_message_is_stored_escaped_and_notified(): void
    {
        $seller = $this->user('seller');
        $p = $this->product($seller);
        $this->actingAs($this->user())->post('/messages/start/product/'.$p->id);
        $c = Conversation::first();
        $this->post('/messages/'.$c->id, ['body' => '<script>alert(1)</script>'])->assertRedirect();
        $this->actingAs($seller)->get('/messages/'.$c->id)->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->assertEquals(1, $seller->notifications()->count());
    }

    public function test_self_conversation_and_pending_listing_thread_are_blocked(): void
    {
        $seller = $this->user('seller');
        $p = $this->product($seller);
        $this->actingAs($seller)->post('/messages/start/product/'.$p->id)->assertForbidden();
        $p->update(['status' => 'pending']);
        $this->actingAs($this->user())->post('/messages/start/product/'.$p->id)->assertForbidden();
    }

    public function test_order_reserves_stock_and_snapshots_server_price(): void
    {
        $p = $this->product();
        $this->actingAs($this->user('employer'))->post('/materials/'.$p->id.'/order', [...$this->orderData(), 'subtotal' => 1, 'total' => 1])->assertRedirect();
        $o = MaterialOrder::first();
        $this->assertEquals(12000000, $o->subtotal);
        $this->assertEquals(17000000, $o->total);
        $this->assertEquals(90, $p->fresh()->stock);
        $this->assertEquals('unverified_external', $o->payment_status);
        $p->update(['price' => 9999999]);
        $this->assertEquals(1200000, $o->items()->first()->unit_price);
    }

    public function test_idempotent_order_does_not_decrement_stock_twice(): void
    {
        $p = $this->product();
        $d = $this->orderData();
        $this->actingAs($this->user())->post('/materials/'.$p->id.'/order', $d)->assertRedirect();
        $this->post('/materials/'.$p->id.'/order', $d)->assertRedirect();
        $this->assertDatabaseCount('material_orders', 1);
        $this->assertEquals(90, $p->fresh()->stock);
    }

    public function test_idempotency_is_scoped_to_buyer(): void
    {
        $p = $this->product();
        $d = $this->orderData();
        $this->actingAs($this->user())->post('/materials/'.$p->id.'/order', $d)->assertRedirect();
        $this->actingAs($this->user())->post('/materials/'.$p->id.'/order', $d)->assertRedirect();
        $this->assertDatabaseCount('material_orders', 2);
        $this->assertEquals(80, $p->fresh()->stock);
    }

    public function test_order_rejects_under_minimum_and_overselling(): void
    {
        $p = $this->product();
        $this->actingAs($this->user())->post('/materials/'.$p->id.'/order', [...$this->orderData(), 'quantity' => 9])->assertSessionHasErrors('quantity');
        $this->post('/materials/'.$p->id.'/order', [...$this->orderData(), 'quantity' => 101])->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('material_orders', 0);
        $this->assertEquals(100, $p->fresh()->stock);
    }

    public function test_online_payment_and_self_purchase_are_rejected(): void
    {
        $seller = $this->user('seller');
        $p = $this->product($seller);
        $this->actingAs($this->user())->post('/materials/'.$p->id.'/order', [...$this->orderData(), 'payment_method' => 'online'])->assertSessionHasErrors('payment_method');
        $this->actingAs($seller)->post('/materials/'.$p->id.'/order', $this->orderData())->assertForbidden();
    }

    public function test_cancel_restores_stock_once_and_is_audited(): void
    {
        $p = $this->product();
        $buyer = $this->user();
        $o = $this->order($buyer, $p);
        $this->patch('/orders/'.$o->id, ['status' => 'cancelled'])->assertRedirect();
        $this->assertEquals(100, $p->fresh()->stock);
        $this->patch('/orders/'.$o->id, ['status' => 'cancelled'])->assertStatus(409);
        $this->assertEquals(100, $p->fresh()->stock);
        $this->assertDatabaseHas('audit_logs', ['action' => 'order.status_changed']);
    }

    public function test_order_lifecycle_is_role_and_transition_checked(): void
    {
        $seller = $this->user('seller');
        $p = $this->product($seller);
        $buyer = $this->user();
        $o = $this->order($buyer, $p);
        $this->patch('/orders/'.$o->id, ['status' => 'confirmed'])->assertForbidden();
        $this->actingAs($seller)->patch('/orders/'.$o->id, ['status' => 'shipped'])->assertStatus(409);
        foreach (['confirmed', 'ready_to_ship', 'shipped'] as $s) {
            $this->patch('/orders/'.$o->id, ['status' => $s])->assertRedirect();
        }$this->patch('/orders/'.$o->id, ['status' => 'delivered'])->assertForbidden();
        $this->actingAs($buyer)->patch('/orders/'.$o->id, ['status' => 'delivered'])->assertRedirect();
        $this->assertEquals('delivered', $o->fresh()->status);
        $this->assertEquals('unverified_external', $o->fresh()->payment_status);
    }

    public function test_unknown_shipping_requires_seller_quote(): void
    {
        $seller = $this->user('seller');
        $p = $this->product($seller, ['shipping_cost' => null]);
        $o = $this->order($this->user(), $p);
        $this->assertNull($o->total);
        $this->actingAs($seller)->patch('/orders/'.$o->id, ['status' => 'confirmed'])->assertSessionHasErrors('shipping_cost');
        $this->patch('/orders/'.$o->id, ['status' => 'confirmed', 'shipping_cost' => 2000000])->assertRedirect();
        $this->assertEquals(14000000, $o->fresh()->total);
    }

    public function test_order_address_hidden_from_unrelated_accounts(): void
    {
        $p = $this->product();
        $buyer = $this->user();
        $o = $this->order($buyer, $p);
        $this->actingAs($this->user())->get('/orders/'.$o->id)->assertForbidden();
        $this->actingAs($buyer)->get('/orders/'.$o->id)->assertOk()->assertSee($o->delivery_address);
        $this->actingAs($this->user('admin'))->get('/orders/'.$o->id)->assertOk();
        $this->patch('/orders/'.$o->id, ['status' => 'confirmed'])->assertForbidden();
    }

    public function test_seller_profile_fields_are_editable(): void
    {
        $u = $this->user('seller');
        $this->actingAs($u)->put('/profile', ['name' => 'نام', 'last_name' => 'فروشنده', 'city' => 'کرج', 'business_name' => 'فروشگاه جدید', 'address' => 'آدرس عمومی جدید'])->assertRedirect();
        $this->assertEquals('فروشگاه جدید', $u->location->fresh()->name);
        $this->assertEquals('آدرس عمومی جدید', $u->location->fresh()->address);
    }

    public function test_audit_csv_download_and_finance_are_not_fake_revenue(): void
    {
        $this->actingAs($this->user('admin'))->get('/admin/audit?export=1')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->get('/admin/finance')->assertOk()->assertSee('نه درآمد تیشه');
    }

    public function test_settings_accept_empty_optional_contact_fields(): void
    {
        $this->actingAs($this->user('admin'))->put('/admin/settings', ['support_phone' => '', 'support_email' => '', 'announcement' => '', 'publication_fee_rial' => 0, 'subscription_fee_rial' => 0])->assertRedirect();
        $this->assertDatabaseHas('settings', ['key' => 'support_email']);
    }

    public function test_admin_can_edit_existing_content_without_slug_collision(): void
    {
        $page = ContentPage::where('slug', 'help')->sole();
        $this->actingAs($this->user('admin'))->put('/admin/pages/'.$page->id, ['slug' => 'help', 'title' => 'راهنمای به‌روز', 'body' => 'محتوای جدید و کامل راهنما برای استفاده کاربران از تیشه', 'published' => 1])->assertRedirect();
        $this->get('/pages/help')->assertSee('راهنمای به‌روز');
    }

    public function test_display_dates_use_persian_digits(): void
    {
        $this->assertSame('۱۴۰۵/۰۸/۰۲', Format::date('2026-10-24'));
    }
}
