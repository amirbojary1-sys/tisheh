<?php

namespace Tests\Feature;

use App\Models\JobCategory;
use App\Models\JobRequest;
use App\Models\MaterialCategory;
use App\Models\MaterialOrder;
use App\Models\MaterialProduct;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class FinalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config(['integrations.demo_enabled' => true]);
    }

    private function user(string $role = 'employer'): User
    {
        $u = new User(['name' => 'آزمون', 'last_name' => 'نصب', 'mobile' => '09'.str_pad((string) random_int(1, 999999999), 9, '0', STR_PAD_LEFT), 'password' => 'CurrentPass2026']);
        $u->role_id = Role::where('name', $role)->sole()->id;
        $u->save();
        $u->profile()->create(['city' => 'تهران']);

        return $u;
    }

    private function product(?User $seller = null, array $extra = []): MaterialProduct
    {
        return MaterialProduct::create(['user_id' => ($seller ?? $this->user('seller'))->id, 'material_category_id' => MaterialCategory::first()->id, 'name' => 'محصول نهایی آزمایش', 'brand' => 'برند تست', 'specifications' => 'مشخصات فنی کافی و قابل بررسی برای تست', 'price' => 1200000, 'unit' => 'bag', 'stock' => 100, 'minimum_order' => 10, 'shipping_cost' => 5000000, 'city' => 'تهران', 'status' => 'published', ...$extra]);
    }

    private function order(User $buyer, MaterialProduct $p): MaterialOrder
    {
        return app(OrderService::class)->create($buyer, $p, ['quantity' => 10, 'idempotency_key' => (string) Str::uuid(), 'delivery_address' => 'آدرس خصوصی تست']);
    }

    private function quoted(): array
    {
        $seller = $this->user('seller');
        $buyer = $this->user();
        $p = $this->product($seller, ['shipping_cost' => null]);
        $o = $this->order($buyer, $p);
        $this->actingAs($seller)->patch('/orders/'.$o->id, ['status' => 'confirmed', 'shipping_cost' => 7000000])->assertRedirect();

        return [$buyer, $seller, $o->fresh(), $p];
    }

    public function test_new_orders_have_reservation_deadline(): void
    {
        $o = $this->order($this->user(), $this->product());
        $this->assertNotNull($o->reservation_expires_at);
        $this->assertEquals(24, round(now()->diffInHours($o->reservation_expires_at)));
    }

    public function test_expiry_releases_stock_exactly_once_and_creates_system_event(): void
    {
        $p = $this->product();
        $o = $this->order($this->user(), $p);
        $this->travel(25)->hours();
        $this->artisan('tisheh:expire-reservations')->expectsOutput('Expired reservations: 1')->assertSuccessful();
        $this->artisan('tisheh:expire-reservations')->expectsOutput('Expired reservations: 0')->assertSuccessful();
        $this->assertEquals(100, $p->fresh()->stock);
        $this->assertEquals('cancelled', $o->fresh()->status);
        $this->assertNull($o->events()->reorder('id', 'desc')->first()->user_id);
        $this->actingAs($o->buyer)->get('/orders/'.$o->id)->assertOk()->assertSee('سیستم');
    }

    public function test_confirmed_orders_are_not_auto_cancelled(): void
    {
        $p = $this->product();
        $o = $this->order($this->user(), $p);
        $this->actingAs($p->user)->patch('/orders/'.$o->id, ['status' => 'confirmed'])->assertRedirect();
        $this->travel(25)->hours();
        $this->artisan('tisheh:expire-reservations')->assertSuccessful();
        $this->assertEquals('confirmed', $o->fresh()->status);
        $this->assertEquals(90, $p->fresh()->stock);
    }

    public function test_legacy_reservations_without_deadline_are_untouched(): void
    {
        $o = $this->order($this->user(), $this->product());
        $o->update(['reservation_expires_at' => null]);
        $this->travel(25)->hours();
        $this->artisan('tisheh:expire-reservations')->expectsOutput('Expired reservations: 0')->assertSuccessful();
        $this->assertEquals('awaiting_seller', $o->fresh()->status);
    }

    public function test_late_seller_confirmation_cannot_revive_expired_order(): void
    {
        $p = $this->product();
        $o = $this->order($this->user(), $p);
        $this->travel(25)->hours();
        $this->actingAs($p->user)->patch('/orders/'.$o->id, ['status' => 'confirmed'])->assertStatus(409);
        $this->assertEquals('cancelled', $o->fresh()->status);
        $this->assertEquals(100, $p->fresh()->stock);
    }

    public function test_new_shipping_quote_blocks_preparation_and_demo_until_buyer_accepts(): void
    {
        [$b,$s,$o] = $this->quoted();
        $this->assertTrue($o->quote_acceptance_required);
        $this->actingAs($s)->patch('/orders/'.$o->id, ['status' => 'ready_to_ship'])->assertStatus(409);
        $this->actingAs($b)->post('/orders/'.$o->id.'/demo-payment')->assertStatus(409);
        $this->get('/orders/'.$o->id)->assertSee('مبلغ نهایی جدید را می‌پذیرم');
        $this->post('/orders/'.$o->id.'/accept-quote', ['expected_total' => $o->total])->assertRedirect();
        $this->assertFalse($o->fresh()->quote_acceptance_required);
        $this->post('/orders/'.$o->id.'/demo-payment')->assertRedirect();
        $this->actingAs($s)->patch('/orders/'.$o->id, ['status' => 'ready_to_ship'])->assertRedirect();
    }

    public function test_accepting_stale_quote_amount_is_rejected(): void
    {
        [$b,$s,$o] = $this->quoted();
        $this->actingAs($b)->post('/orders/'.$o->id.'/accept-quote', ['expected_total' => 1])->assertStatus(409);
        $this->assertTrue($o->fresh()->quote_acceptance_required);
    }

    public function test_only_buyer_can_accept_quote(): void
    {
        [$b,$s,$o] = $this->quoted();
        $this->actingAs($s)->post('/orders/'.$o->id.'/accept-quote', ['expected_total' => $o->total])->assertForbidden();
        $this->actingAs($this->user('admin'))->post('/orders/'.$o->id.'/accept-quote', ['expected_total' => $o->total])->assertForbidden();
    }

    public function test_buyer_can_decline_changed_quote_and_stock_returns(): void
    {
        [$b,$s,$o,$p] = $this->quoted();
        $this->actingAs($b)->patch('/orders/'.$o->id, ['status' => 'cancelled'])->assertRedirect();
        $this->assertEquals(100, $p->fresh()->stock);
    }

    public function test_quote_acceptance_replay_does_not_duplicate_audit(): void
    {
        [$b,$s,$o] = $this->quoted();
        for ($i = 0; $i < 2; $i++) {
            $this->actingAs($b)->post('/orders/'.$o->id.'/accept-quote', ['expected_total' => $o->total])->assertRedirect();
        }$this->assertEquals(1, DB::table('audit_logs')->where('action', 'order.quote_accepted')->count());
        $this->assertEquals('unverified_external', $o->fresh()->payment_status);
    }

    public function test_password_change_requires_current_password(): void
    {
        $u = $this->user();
        $this->actingAs($u)->put('/account/password', ['current_password' => 'WrongPass2026', 'password' => 'ChangedPass2026', 'password_confirmation' => 'ChangedPass2026'])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('CurrentPass2026', $u->fresh()->password));
    }

    public function test_password_change_updates_hash_revokes_sessions_and_logs_out(): void
    {
        $u = $this->user();
        DB::table('sessions')->insert(['id' => 'other-test-session', 'user_id' => $u->id, 'payload' => '', 'last_activity' => time()]);
        $this->actingAs($u)->put('/account/password', ['current_password' => 'CurrentPass2026', 'password' => 'ChangedPass2026', 'password_confirmation' => 'ChangedPass2026'])->assertRedirect('/login');
        $this->assertGuest();
        $this->assertTrue(Hash::check('ChangedPass2026', $u->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['user_id' => $u->id]);
        $log = DB::table('audit_logs')->where('action', 'password.changed')->sole();
        $this->assertStringNotContainsString('ChangedPass2026', json_encode($log));
    }

    public function test_password_values_are_never_flashed_on_error(): void
    {
        $u = $this->user();
        $this->actingAs($u)->from('/dashboard')->put('/account/password', ['current_password' => 'CurrentPass2026', 'password' => 'Bad', 'password_confirmation' => 'Bad'])->assertSessionHasErrors('password')->assertSessionMissing('_old_input.current_password')->assertSessionMissing('_old_input.password_confirmation')->assertSessionMissing('_old_input.password');
    }

    public function test_guest_cannot_change_password(): void
    {
        $this->put('/account/password', [])->assertRedirect('/login');
    }

    public function test_owner_can_delete_uploaded_image_and_file(): void
    {
        Storage::fake('public');
        $s = $this->user('seller');
        $p = $this->product($s);
        $path = 'products/'.Str::uuid().'.webp';
        Storage::disk('public')->put($path, 'test-image');
        $image = $p->images()->create(['path' => $path]);
        $this->actingAs($s)->delete('/materials/'.$p->id.'/images/'.$image->id)->assertRedirect();
        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_other_seller_cannot_delete_image(): void
    {
        $p = $this->product();
        $image = $p->images()->create(['path' => 'products/'.Str::uuid().'.webp']);
        $this->actingAs($this->user('seller'))->delete('/materials/'.$p->id.'/images/'.$image->id)->assertForbidden();
    }

    public function test_image_of_another_product_cannot_be_deleted(): void
    {
        $s = $this->user('seller');
        $p = $this->product($s);
        $other = $this->product($s);
        $image = $other->images()->create(['path' => 'products/'.Str::uuid().'.webp']);
        $this->actingAs($s)->delete('/materials/'.$p->id.'/images/'.$image->id)->assertNotFound();
        $this->assertDatabaseHas('product_images', ['id' => $image->id]);
    }

    public function test_more_than_eight_images_are_rejected(): void
    {
        Storage::fake('public');
        $s = $this->user('seller');
        $p = $this->product($s);
        for ($i = 0; $i < 8; $i++) {
            $p->images()->create(['path' => 'products/'.Str::uuid().'.webp']);
        }$d = $p->only('name', 'brand', 'specifications', 'price', 'unit', 'stock', 'minimum_order', 'shipping_cost', 'city');
        $d += ['category_id' => $p->material_category_id, 'status' => 'pending', 'image' => UploadedFile::fake()->image('image.png')];
        $this->actingAs($s)->put('/materials/'.$p->id, $d)->assertSessionHasErrors('image');
        $this->assertEquals(8, $p->images()->count());
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_public_image_fallback_serves_only_allowed_public_paths(): void
    {
        Storage::fake('public');
        $file = Str::uuid().'.webp';
        Storage::disk('public')->put('products/'.$file, 'webp-test');
        $this->get('/storage/products/'.$file)->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->get('/storage/products/.env')->assertNotFound();
        $this->get('/storage/private/test.txt')->assertNotFound();
    }

    private function job(string $title, int $wage, string $date): JobRequest
    {
        return JobRequest::create(['user_id' => $this->user()->id, 'job_category_id' => JobCategory::first()->id, 'title' => $title, 'description' => 'توضیحات معتبر برای تست فیلتر کار', 'city' => 'تهران', 'address' => 'آدرس خصوصی', 'workers_count' => 2, 'start_date' => $date, 'duration_days' => 10, 'wage' => $wage, 'payment_type' => 'daily', 'status' => 'published']);
    }

    public function test_wage_and_start_date_filters_are_inclusive(): void
    {
        $date = now()->addDays(5)->format('Y-m-d');
        $this->job('عنوان هدف فیلتر', 2000000, $date);
        $this->job('عنوان خارج محدوده', 9000000, $date);
        $this->get('/jobs?'.http_build_query(['min_wage' => 1000000, 'max_wage' => 3000000, 'start_from' => $date, 'start_to' => $date]))->assertOk()->assertSee('عنوان هدف فیلتر')->assertDontSee('عنوان خارج محدوده');
    }

    public function test_upper_bound_filters_work_without_lower_bound(): void
    {
        $this->get('/jobs?max_wage=3000000&start_to=2027-12-31')->assertOk();
    }

    public function test_reversed_filter_ranges_are_rejected(): void
    {
        $this->get('/jobs?min_wage=3000000&max_wage=1000000')->assertSessionHasErrors('max_wage');
        $this->get('/jobs?start_from=2027-02-01&start_to=2027-01-01')->assertSessionHasErrors('start_to');
    }

    public function test_admin_can_start_review_then_publish_and_owner_can_track(): void
    {
        $j = $this->job('درخواست مرحله بررسی', 2000000, now()->addDays(2)->format('Y-m-d'));
        $j->update(['status' => 'pending']);
        $a = $this->user('admin');
        $this->actingAs($a)->patch('/admin/moderate/job/'.$j->id, ['status' => 'in_review'])->assertRedirect();
        $this->assertEquals('in_review', $j->fresh()->status);
        $this->get('/admin/listings/job?status=in_review')->assertOk()->assertSee('تأیید و انتشار');
        $this->actingAs($j->user)->get('/jobs/'.$j->id)->assertOk()->assertSee('در حال بررسی آگهی');
        $this->actingAs($a)->patch('/admin/moderate/job/'.$j->id, ['status' => 'published'])->assertRedirect();
        $this->assertEquals('published', $j->fresh()->status);
    }

    public function test_system_monitor_is_admin_only_and_never_prints_key(): void
    {
        $this->actingAs($this->user())->get('/admin/system')->assertForbidden();
        $this->actingAs($this->user('admin'))->get('/admin/system')->assertOk()->assertDontSee(config('app.key'));
    }

    public function test_doctor_fails_unsafe_production_config_without_secret_output(): void
    {
        $u = $this->user('admin');
        Artisan::call('tisheh:doctor', ['--production' => true]);
        $out = Artisan::output();
        $this->assertStringContainsString('FAIL', $out);
        $this->assertStringNotContainsString(config('app.key'), $out);
    }

    public function test_cpanel_publisher_generates_only_public_files(): void
    {
        $dest = sys_get_temp_dir().'/tisheh-publish-'.Str::random(12);
        File::makeDirectory($dest);
        try {
            $this->artisan('tisheh:cpanel-publish', ['--public-path' => $dest, '--no-symlink' => true])->assertSuccessful();
            $this->assertFileExists($dest.'/index.php');
            $this->assertFileExists($dest.'/css/reference.css');
            $this->assertFileDoesNotExist($dest.'/.env');
            $this->assertDirectoryDoesNotExist($dest.'/vendor');
            $this->assertStringContainsString(var_export(base_path(), true), file_get_contents($dest.'/index.php'));
            $this->artisan('tisheh:cpanel-publish', ['--public-path' => $dest])->assertFailed();
        } finally {
            File::deleteDirectory($dest);
        }
    }

    public function test_cpanel_publisher_refuses_private_app_and_existing_secrets(): void
    {
        $this->artisan('tisheh:cpanel-publish', ['--public-path' => base_path(), '--force' => true])->assertFailed();
        $dest = sys_get_temp_dir().'/tisheh-publish-'.Str::random(12);
        File::makeDirectory($dest);
        File::put($dest.'/.env', 'test');
        try {
            $this->artisan('tisheh:cpanel-publish', ['--public-path' => $dest, '--force' => true])->assertFailed();
            $this->assertFileDoesNotExist($dest.'/index.php');
        } finally {
            File::deleteDirectory($dest);
        }
    }

    public function test_array_textarea_input_does_not_crash_dashboard_on_redirect(): void
    {
        $u = $this->user();
        $this->actingAs($u)->from('/dashboard')->put('/profile', ['name' => 'نام', 'last_name' => 'خانوادگی', 'city' => 'تهران', 'bio' => ['invalid']])->assertSessionHasErrors('bio');
        $this->get('/dashboard')->assertOk();
    }

    public function test_array_password_registration_is_validation_error_not_crash(): void
    {
        $this->post('/register', ['password' => ['invalid']])->assertSessionHasErrors('password');
    }

    public function test_password_change_rejects_more_than_72_bytes(): void
    {
        $u = $this->user();
        $password = 'Aa2026'.str_repeat('الف', 20);
        $this->actingAs($u)->put('/account/password', ['current_password' => 'CurrentPass2026', 'password' => $password, 'password_confirmation' => $password])->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('CurrentPass2026', $u->fresh()->password));
    }
}
