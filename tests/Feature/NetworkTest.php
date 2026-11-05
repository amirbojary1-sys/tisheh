<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\JobCategory;
use App\Models\JobRequest;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\DirectoryService;
use App\Services\IdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NetworkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function user(string $role = 'worker'): User
    {
        $u = new User(['name' => 'کاربر', 'last_name' => 'آزمایشی', 'mobile' => '09'.str_pad((string) random_int(1, 999999999), 9, '0', STR_PAD_LEFT), 'password' => 'SecurePass2026']);
        $u->role_id = Role::where('name', $role)->sole()->id;
        $u->save();
        $u->profile()->create(['city' => 'تهران', 'specialty' => 'برق‌کاری']);

        return $u;
    }

    private function identity(): array
    {
        return ['national_id' => '1234567891', 'contact_phone' => '02112345678', 'postal_code' => '1234567890', 'private_address' => 'آدرس خصوصی صرفاً آزمایشی برای آزمون', 'current_password' => 'SecurePass2026'];
    }

    private function professional(array $extra = []): array
    {
        return [...['headline' => 'متخصص آزمایشی سیم‌کشی', 'city' => 'تهران', 'specialty' => 'برق‌کاری', 'bio' => 'این معرفی کاملاً آزمایشی برای بررسی عملکرد شبکه ساختمان است.', 'activity_type' => 'engineer', 'experience_years' => 8, 'availability' => 'available', 'skills' => [JobCategory::first()->id], 'directory_enabled' => 1, 'public_contact' => 0], ...$extra];
    }

    private function published(?User $u = null): User
    {
        $u ??= $this->user();
        app(DirectoryService::class)->save($u, $this->professional());
        $u->profile()->update(['directory_status' => 'published']);

        return $u->fresh();
    }

    private function job(User $employer, int $count = 1): JobRequest
    {
        return JobRequest::create(['user_id' => $employer->id, 'job_category_id' => JobCategory::first()->id, 'title' => 'پروژه آزمایشی شبکه', 'description' => str_repeat('شرح معتبر آزمایش. ', 4), 'city' => 'تهران', 'address' => 'خصوصی', 'workers_count' => $count, 'start_date' => now()->addDay(), 'duration_days' => 20, 'payment_type' => 'project', 'status' => 'published']);
    }

    public function test_signup_without_private_fields_still_works(): void
    {
        $this->post('/register', ['name' => 'تست', 'last_name' => 'ثبت', 'mobile' => '09123456780', 'password' => 'SecurePass2026', 'password_confirmation' => 'SecurePass2026', 'role' => 'employer', 'city' => 'تهران', 'terms' => 1])->assertRedirect('/dashboard');
        $this->assertNull(User::where('mobile', '09123456780')->first()->profile->national_id);
    }

    public function test_signup_supports_all_roles_and_encrypts_optional_identity(): void
    {
        $d = [...$this->identity(), 'name' => 'تست', 'last_name' => 'چندنقشی', 'mobile' => '09123456781', 'password' => 'SecurePass2026', 'password_confirmation' => 'SecurePass2026', 'role' => 'employer', 'additional_roles' => ['worker', 'seller'], 'city' => 'تهران', 'terms' => 1, 'specialty' => 'برق', 'business_name' => 'فروشگاه نمونه', 'address' => 'آدرس عمومی فروشگاه آزمایشی'];
        unset($d['current_password']);
        $this->post('/register', $d)->assertRedirect('/dashboard');
        $u = User::where('mobile', $d['mobile'])->first();
        foreach (['worker', 'employer', 'seller'] as $role) {
            $this->assertTrue($u->hasRole($role));
        }$this->assertTrue($u->hasPermission('jobs.create'));
        $this->assertTrue($u->hasPermission('products.create'));
        $this->assertFalse($u->hasPermission('admin.access'));
        $this->assertEquals($d['national_id'], $u->profile->national_id);
        $raw = DB::table('user_profiles')->where('user_id', $u->id)->first();
        foreach (['national_id', 'contact_phone', 'private_address', 'postal_code'] as $key) {
            $this->assertNotEquals($d[$key], $raw->$key);
        }$this->assertStringNotContainsString($d['national_id'], $u->profile->toJson());
        $this->assertNull($u->mobile_verified_at);
    }

    public function test_invalid_private_values_are_rejected_and_not_flashed(): void
    {
        $u = $this->user();
        $this->actingAs($u)->from('/account/identity')->put('/account/identity', [...$this->identity(), 'national_id' => '1111111111', 'postal_code' => '0000000000', 'contact_phone' => 'abc'])->assertSessionHasErrors(['national_id', 'postal_code', 'contact_phone'])->assertSessionMissing('_old_input.national_id')->assertSessionMissing('_old_input.private_address');
        $this->assertNull($u->fresh()->profile->national_id);
    }

    public function test_private_digits_are_normalized_without_losing_leading_zero(): void
    {
        $u = $this->user();
        $this->actingAs($u)->put('/account/identity', [...$this->identity(), 'national_id' => '۱۲۳۴۵۶۷۸۹۱', 'contact_phone' => '۰۲۱۱۲۳۴۵۶۷۸', 'postal_code' => '۱۲۳۴۵۶۷۸۹۰'])->assertSessionHasNoErrors();
        $this->assertSame('02112345678', $u->fresh()->profile->contact_phone);
        $this->assertSame('1234567891', $u->fresh()->profile->national_id);
    }

    public function test_identity_update_requires_current_password(): void
    {
        $u = $this->user();
        $this->actingAs($u)->put('/account/identity', [...$this->identity(), 'current_password' => 'wrong'])->assertSessionHasErrors('current_password');
        $this->assertNull($u->fresh()->profile->national_id);
    }

    public function test_duplicate_national_id_does_not_create_partial_account(): void
    {
        $u = $this->user();
        app(IdentityService::class)->save($u, $this->identity());
        $other = $this->user();
        $this->actingAs($other)->put('/account/identity', $this->identity())->assertSessionHasErrors('national_id');
        $this->assertNull($other->fresh()->profile->national_id);
        $this->assertSame(1, UserProfile::whereNotNull('national_id_fingerprint')->count());
    }

    public function test_review_requires_complete_fields_and_never_officially_verifies(): void
    {
        $u = $this->user();
        $this->actingAs($u)->post('/account/identity/submit')->assertSessionHasErrors('identity');
        $this->put('/account/identity', $this->identity())->assertSessionHasNoErrors();
        $this->post('/account/identity/submit')->assertSessionHasNoErrors();
        $p = $u->fresh()->profile;
        $this->assertSame('pending', $p->identity_status);
        $admin = $this->user('admin');
        $this->actingAs($admin)->post('/admin/identities/'.$u->id, ['revision' => $p->identity_revision, 'decision' => 'reviewed', 'note' => 'فقط کامل‌بودن و سازگاری اطلاعات بررسی شد.'])->assertSessionHasNoErrors();
        $this->assertSame('reviewed', $u->fresh()->profile->identity_status);
        $this->assertNull($u->fresh()->mobile_verified_at);
    }

    public function test_identity_changes_invalidate_review_and_stale_admin_actions(): void
    {
        $u = $this->user();
        app(IdentityService::class)->save($u, $this->identity());
        $this->actingAs($u)->post('/account/identity/submit');
        $rev = $u->fresh()->profile->identity_revision;
        $this->put('/account/identity', [...$this->identity(), 'private_address' => 'نشانی جدید خصوصی کاملاً آزمایشی'])->assertSessionHasNoErrors();
        $this->actingAs($this->user('admin'))->post('/admin/identities/'.$u->id, ['revision' => $rev, 'decision' => 'reviewed', 'note' => 'یادداشت بررسی اداری آزمایشی'])->assertStatus(409);
        $this->assertSame('not_submitted', $u->fresh()->profile->identity_status);
    }

    public function test_identity_access_is_private_audited_and_masked_for_owner(): void
    {
        $u = $this->user();
        app(IdentityService::class)->save($u, $this->identity());
        $this->actingAs($u)->get('/account/identity')->assertOk()->assertDontSee('1234567891')->assertSee('******7891');
        $other = $this->user();
        $this->actingAs($other)->get('/admin/identities/'.$u->id)->assertForbidden();
        $admin = $this->user('admin');
        $this->actingAs($admin)->get('/admin/identities/'.$u->id)->assertOk()->assertSee('1234567891');
        $this->assertDatabaseHas('audit_logs', ['action' => 'identity.viewed', 'user_id' => $admin->id, 'subject_id' => $u->id]);
        $this->assertStringNotContainsString('1234567891', AuditLog::all()->toJson());
    }

    public function test_private_identity_can_be_removed(): void
    {
        $u = $this->user();
        app(IdentityService::class)->save($u, $this->identity());
        $this->actingAs($u)->put('/account/identity', ['clear_national_id' => 1, 'contact_phone' => '', 'postal_code' => '', 'private_address' => '', 'current_password' => 'SecurePass2026'])->assertSessionHasNoErrors();
        $p = $u->fresh()->profile;
        foreach (['national_id', 'national_id_fingerprint', 'contact_phone', 'postal_code', 'private_address'] as $key) {
            $this->assertNull($p->$key);
        }
    }

    public function test_roles_cannot_grant_admin_and_existing_primary_works(): void
    {
        $u = $this->user('employer');
        $this->assertTrue($u->hasPermission('jobs.create'));
        $this->actingAs($u)->put('/account/roles', ['roles' => ['worker', 'admin']])->assertSessionHasErrors('roles.1');
        $this->assertFalse($u->fresh()->hasRole('admin'));
        $this->put('/account/roles', ['roles' => ['worker', 'employer']])->assertSessionHasNoErrors();
        $this->assertTrue($u->fresh()->hasRole('worker'));
        $this->assertTrue($u->fresh()->hasPermission('jobs.create'));
    }

    public function test_seller_role_needs_store_and_preserves_history_on_removal(): void
    {
        $u = $this->user();
        $this->actingAs($u)->put('/account/roles', ['roles' => ['seller']])->assertSessionHasErrors('business_name');
        $this->put('/account/roles', ['roles' => ['worker', 'seller'], 'business_name' => 'فروشگاه آزمایشی', 'address' => 'آدرس عمومی آزمایشی'])->assertSessionHasNoErrors();
        $this->assertTrue($u->fresh()->hasPermission('products.create'));
        $this->put('/account/roles', ['roles' => ['worker']])->assertSessionHasNoErrors();
        $this->assertFalse($u->fresh()->hasPermission('products.create'));
        $this->assertNotNull($u->fresh()->location);
    }

    public function test_removing_original_role_does_not_leave_legacy_privilege(): void
    {
        $u = $this->user('employer');
        $this->actingAs($u)->put('/account/roles', ['roles' => ['worker']])->assertSessionHasNoErrors();
        $this->assertFalse($u->fresh()->hasRole('employer'));
        $this->assertFalse($u->fresh()->hasPermission('jobs.create'));
        $this->assertTrue($u->fresh()->hasRole('worker'));
    }

    public function test_admin_cannot_change_own_roles_through_public_form(): void
    {
        $u = $this->user('admin');
        $this->actingAs($u)->get('/account/roles')->assertForbidden();
        $this->put('/account/roles', ['roles' => ['worker']])->assertForbidden();
        $this->assertTrue($u->fresh()->hasRole('admin'));
    }

    public function test_directory_is_opt_in_and_moderated_not_identity_gated(): void
    {
        $u = $this->user();
        $this->actingAs($u)->put('/account/professional', $this->professional())->assertSessionHasNoErrors();
        $this->get('/professionals/'.$u->id)->assertNotFound();
        $p = $u->fresh()->profile;
        $this->actingAs($this->user('admin'))->post('/admin/professionals/'.$u->id, ['revision' => $p->directory_revision, 'decision' => 'published', 'note' => 'محتوای نمونه برای انتشار بررسی شد.'])->assertSessionHasNoErrors();
        $this->get('/professionals/'.$u->id)->assertOk();
        $this->assertNull($u->fresh()->profile->national_id);
    }

    public function test_directory_filters_and_private_information_never_leaks(): void
    {
        $u = $this->published();
        app(IdentityService::class)->save($u, $this->identity());
        $this->get('/professionals?activity=engineer&city='.urlencode('تهران'))->assertOk()->assertSee($u->profile->headline)->assertDontSee('1234567891')->assertDontSee('02112345678');
        $this->get('/professionals?activity=architect')->assertOk()->assertDontSee($u->profile->headline);
        $this->get('/professionals/'.$u->id)->assertOk()->assertDontSee($u->mobile)->assertDontSee('1234567890')->assertDontSee($this->identity()['private_address']);
    }

    public function test_editing_shared_profile_hides_previously_published_professional(): void
    {
        $u = $this->published();
        $this->actingAs($u)->put('/profile', ['name' => 'تغییر', 'last_name' => 'نام', 'city' => 'تهران', 'bio' => 'معرفی تازه و آزمایشی پس از انتشار پروفایل'])->assertSessionHasNoErrors();
        $this->get('/professionals/'.$u->id)->assertNotFound();
        $this->assertSame('pending', $u->fresh()->profile->directory_status);
    }

    public function test_directory_admin_revision_protects_against_stale_publication(): void
    {
        $u = $this->user();
        app(DirectoryService::class)->save($u, $this->professional());
        $rev = $u->fresh()->profile->directory_revision;
        app(DirectoryService::class)->save($u, $this->professional(['headline' => 'عنوان تازه آزمایشی']));
        $this->actingAs($this->user('admin'))->post('/admin/professionals/'.$u->id, ['revision' => $rev, 'decision' => 'published', 'note' => 'بررسی نسخه قدیمی'])->assertStatus(409);
    }

    public function test_withdraw_publication_and_moderation_takedown_hide_directory(): void
    {
        $u = $this->published();
        $this->actingAs($this->user('admin'))->post('/admin/professionals/'.$u->id, ['revision' => $u->profile->directory_revision, 'decision' => 'rejected', 'note' => 'محتوای نیازمند اصلاح برای آزمایش توقف انتشار'])->assertSessionHasNoErrors();
        $this->get('/professionals/'.$u->id)->assertNotFound();
        $u = $this->published();
        $this->actingAs($u)->put('/account/professional', $this->professional(['directory_enabled' => 0]))->assertSessionHasNoErrors();
        $this->get('/professionals/'.$u->id)->assertNotFound();
    }

    public function test_suspension_immediately_hides_profile_and_blocks_contact(): void
    {
        $u = $this->published();
        $u->status = 'suspended';
        $u->save();
        $this->get('/professionals/'.$u->id)->assertNotFound();
        $this->actingAs($this->user('employer'))->post('/messages/start/professional/'.$u->id)->assertNotFound();
    }

    public function test_professional_contact_requires_auth_consent_and_real_published_profile(): void
    {
        $u = $this->published();
        $this->post('/messages/start/professional/'.$u->id)->assertRedirect('/login');
        $this->actingAs($this->user('employer'))->get('/professionals/'.$u->id)->assertOk()->assertDontSee('tel:'.$u->mobile);
        $u->profile()->update(['public_contact' => true]);
        $this->get('/professionals/'.$u->id)->assertSee('tel:'.$u->mobile);
        $this->post('/messages/start/professional/'.$u->id)->assertRedirect();
        $this->assertDatabaseHas('conversations', ['context_type' => 'professional', 'context_id' => $u->id, 'recipient_id' => $u->id]);
    }

    public function test_professional_report_reuses_private_support_workflow(): void
    {
        $u = $this->published();
        $this->actingAs($this->user())->post('/reports', ['subject_type' => 'professional', 'subject_id' => $u->id, 'reason' => 'گزارش آزمایشی', 'description' => 'شرح کافی برای گزارش یک پروفایل حرفه‌ای آزمایشی'])->assertRedirect('/reports');
        $this->assertDatabaseHas('reports', ['subject_type' => 'professional', 'subject_id' => $u->id]);
    }

    public function test_private_portfolio_images_are_sanitized_and_moderation_gated(): void
    {
        Storage::fake('local');
        $u = $this->user();
        $this->actingAs($u)->post('/account/portfolio', ['title' => 'اجرای آزمایشی', 'description' => 'شرح نمونه‌کار مجاز و کاملاً آزمایشی ساختمان', 'image' => UploadedFile::fake()->image('photo.jpg', 2000, 1000)])->assertSessionHasNoErrors();
        $entry = $u->portfolioEntries()->first();
        $this->assertNotNull($entry);
        $bytes = Storage::disk('local')->get($entry->image_path);
        $this->assertSame('WEBP', substr($bytes, 8, 4));
        $size = getimagesizefromstring($bytes);
        $this->assertEquals(1600, $size[0]);
        $this->get('/professionals/portfolio/'.$entry->id.'/image')->assertOk();
        $this->actingAs($this->user())->get('/professionals/portfolio/'.$entry->id.'/image')->assertNotFound();
        $u = $this->published($u);
        $this->get('/professionals/portfolio/'.$entry->id.'/image')->assertOk();
        $u->profile()->update(['directory_enabled' => false]);
        $this->get('/professionals/portfolio/'.$entry->id.'/image')->assertNotFound();
    }

    public function test_portfolio_ownership_deletion_and_svg_rejection(): void
    {
        Storage::fake('local');
        $u = $this->user();
        $this->actingAs($u)->post('/account/portfolio', ['title' => 'نمونه', 'description' => 'توضیحات کافی برای آزمون امنیت فایل', 'image' => UploadedFile::fake()->createWithContent('x.svg', '<svg onload="alert(1)"></svg>')])->assertSessionHasErrors('image');
        $this->post('/account/portfolio', ['title' => 'نمونه', 'description' => 'توضیحات کافی برای آزمون فایل مجاز', 'image' => UploadedFile::fake()->image('x.png')])->assertSessionHasNoErrors();
        $entry = $u->portfolioEntries()->first();
        $this->actingAs($this->user())->delete('/account/portfolio/'.$entry->id)->assertForbidden();
        $this->actingAs($u)->delete('/account/portfolio/'.$entry->id)->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('portfolio_entries', ['id' => $entry->id]);
        Storage::disk('local')->assertMissing($entry->image_path);
    }

    public function test_portfolio_upload_limit_and_edit_re_review(): void
    {
        Storage::fake('local');
        $u = $this->published();
        for ($i = 0; $i < 8; $i++) {
            $u->portfolioEntries()->create(['title' => 'نمونه '.$i, 'description' => 'شرح نمونه‌کار آزمایشی']);
        }$this->actingAs($u)->post('/account/portfolio', ['title' => 'نمونه اضافه', 'description' => 'شرح کافی برای نمونه‌کار اضافه', 'image' => UploadedFile::fake()->image('x.png')])->assertSessionHasErrors('portfolio');
        $this->assertCount(0, Storage::disk('local')->allFiles('portfolio'));
        $entry = $u->portfolioEntries()->first();
        $this->delete('/account/portfolio/'.$entry->id)->assertSessionHasNoErrors();
        $this->assertSame('pending', $u->fresh()->profile->directory_status);
    }

    public function test_professional_cannot_self_publish_or_forge_private_review(): void
    {
        $u = $this->user();
        $this->actingAs($u)->put('/account/professional', [...$this->professional(), 'directory_status' => 'published', 'identity_status' => 'reviewed', 'national_id' => '1234567891'])->assertSessionHasNoErrors();
        $p = $u->fresh()->profile;
        $this->assertSame('pending', $p->directory_status);
        $this->assertSame('not_submitted', $p->identity_status);
        $this->assertNull($p->national_id);
        $this->post('/admin/professionals/'.$u->id, ['revision' => 1, 'decision' => 'published', 'note' => 'انتشار غیرمجاز'])->assertForbidden();
    }

    public function test_role_removal_hides_public_professional_but_keeps_portfolio(): void
    {
        $u = $this->published();
        $entry = $u->portfolioEntries()->create(['title' => 'نمونه', 'description' => 'شرح نمونه‌کار مجاز']);
        $this->actingAs($u)->put('/account/roles', ['roles' => ['employer']])->assertSessionHasNoErrors();
        $this->get('/professionals/'.$u->id)->assertNotFound();
        $this->assertDatabaseHas('portfolio_entries', ['id' => $entry->id]);
    }

    public function test_worker_can_withdraw_and_employer_cannot_accept_withdrawn_application(): void
    {
        $e = $this->user('employer');
        $w = $this->user();
        $job = $this->job($e);
        $a = $job->applications()->create(['user_id' => $w->id, 'message' => 'اعلام آمادگی آزمایشی']);
        $this->actingAs($this->user())->post('/applications/'.$a->id.'/withdraw')->assertForbidden();
        $this->actingAs($w)->post('/applications/'.$a->id.'/withdraw')->assertSessionHasNoErrors();
        $this->assertSame('withdrawn', $a->fresh()->status);
        $this->actingAs($e)->patch('/applications/'.$a->id, ['status' => 'accepted'])->assertStatus(409);
    }

    public function test_employer_cannot_exceed_staff_capacity_or_review_closed_job(): void
    {
        $e = $this->user('employer');
        $job = $this->job($e);
        $a = $job->applications()->create(['user_id' => $this->user()->id, 'message' => 'اول', 'status' => 'accepted']);
        $b = $job->applications()->create(['user_id' => $this->user()->id, 'message' => 'دوم']);
        $this->actingAs($e)->patch('/applications/'.$b->id, ['status' => 'accepted'])->assertSessionHasErrors('status');
        $job->update(['status' => 'completed']);
        $this->patch('/applications/'.$a->id, ['status' => 'rejected'])->assertStatus(409);
    }

    public function test_new_pages_render_for_owners_and_authorized_admin(): void
    {
        $u = $this->user();
        $this->actingAs($u);
        foreach (['/account/identity', '/account/roles', '/account/professional', '/professionals'] as $path) {
            $this->get($path)->assertOk();
        }$this->actingAs($this->user('admin'));
        foreach (['/admin/identities', '/admin/identities/'.$u->id, '/admin/professionals'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_portfolio_metadata_edit_preserves_photo_and_checks_ownership(): void
    {
        Storage::fake('local');
        $u = $this->published();
        $entry = $u->portfolioEntries()->create(['title' => 'قدیمی', 'description' => 'شرح قدیمی آزمایشی نمونه‌کار', 'image_path' => 'portfolio/test.webp']);
        Storage::disk('local')->put('portfolio/test.webp', 'test');
        $data = ['title' => 'عنوان اصلاح‌شده', 'description' => 'شرح جدید معتبر و آزمایشی نمونه‌کار', 'completed_year' => 1405];
        $this->actingAs($this->user())->put('/account/portfolio/'.$entry->id, $data)->assertForbidden();
        $this->actingAs($u)->put('/account/portfolio/'.$entry->id, $data)->assertSessionHasNoErrors();
        $this->assertSame('عنوان اصلاح‌شده', $entry->fresh()->title);
        $this->assertSame('portfolio/test.webp', $entry->fresh()->image_path);
        $this->assertSame('pending', $u->fresh()->profile->directory_status);
    }

    public function test_admin_professional_report_links_to_correct_subject(): void
    {
        $u = $this->published();
        $report = Report::create(['user_id' => $this->user()->id, 'subject_type' => 'professional', 'subject_id' => $u->id, 'reason' => 'گزارش تست', 'description' => 'شرح گزارش آزمایشی']);
        $this->actingAs($this->user('admin'))->get('/admin/reports/'.$report->id)->assertOk()->assertSee(route('admin.professionals', ['user' => $u->id]), false);
        $this->get('/admin/professionals?user='.$u->id)->assertOk()->assertSee($u->profile->headline);
    }

    public function test_duplicate_identity_registration_rolls_back_new_user_and_roles(): void
    {
        $u = $this->user();
        app(IdentityService::class)->save($u, $this->identity());
        $before = User::count();
        $this->post('/register', [...$this->identity(), 'name' => 'ثبت', 'last_name' => 'تکراری', 'mobile' => '09123456789', 'role' => 'employer', 'city' => 'تهران', 'terms' => 1, 'password' => 'SecurePass2026', 'password_confirmation' => 'SecurePass2026'])->assertSessionHasErrors('national_id');
        $this->assertSame($before, User::count());
        $this->assertDatabaseMissing('users', ['mobile' => '09123456789']);
    }

    public function test_multirole_new_activity_menu_exposes_only_allowed_actions(): void
    {
        $u = $this->user('employer');
        $u->roles()->sync(Role::whereIn('name', ['worker', 'employer', 'seller'])->pluck('id'));
        $this->actingAs($u)->get('/account/new')->assertOk()->assertSee(route('jobs.create'), false)->assertSee(route('products.create'), false)->assertSee(route('account.professional'), false);
        $this->actingAs($this->user())->get('/account/new')->assertOk()->assertDontSee(route('products.create'), false);
    }

    public function test_registration_records_engineering_activity_without_claiming_verification(): void
    {
        $this->post('/register', ['name' => 'مهندس', 'last_name' => 'آزمایشی', 'mobile' => '09111112222', 'password' => 'SecurePass2026', 'password_confirmation' => 'SecurePass2026', 'role' => 'worker', 'city' => 'تهران', 'specialty' => 'عمران', 'activity_type' => 'engineer', 'terms' => 1])->assertRedirect('/dashboard');
        $u = User::where('mobile', '09111112222')->first();
        $this->assertSame('engineer', $u->profile->activity_type);
        $this->assertFalse($u->profile->directory_enabled);
        $this->assertSame('not_submitted', $u->profile->identity_status);
    }

    public function test_admin_filter_and_role_member_counts_include_additional_roles(): void
    {
        $u = $this->user('employer');
        $u->roles()->sync(Role::whereIn('name', ['employer', 'worker'])->pluck('id'));
        $this->actingAs($this->user('admin'))->get('/admin/users?role=worker')->assertOk()->assertSee(route('admin.users.show', $u), false);
        $roles = $this->get('/admin/roles')->assertOk()->viewData('roles');
        $this->assertEquals(1, $roles->firstWhere('name', 'worker')->users_count);
    }

    public function test_malformed_numeric_route_ids_return_404_not_server_errors(): void
    {
        $this->actingAs($this->user('admin'));
        foreach (['/messages/start/professional/abc', '/admin/moderate/job/abc'] as $path) {
            $this->post($path)->assertNotFound();
        }
        $this->put('/admin/categories/job/abc')->assertNotFound();
        $this->patch('/admin/listings/job/abc')->assertNotFound();
    }

    public function test_legacy_account_missing_profile_can_repair_it_without_false_success(): void
    {
        $u = $this->user();
        $u->profile()->delete();
        $this->actingAs($u)->put('/profile', ['name' => 'تست', 'last_name' => 'ترمیم', 'city' => 'تهران', 'bio' => 'معرفی آزمایشی'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('user_profiles', ['user_id' => $u->id, 'city' => 'تهران']);
        $this->get('/account/professional')->assertOk();
    }

    public function test_setup_preserves_existing_encryption_key_and_private_data(): void
    {
        $u = $this->user();
        app(IdentityService::class)->save($u, $this->identity());
        $key = config('app.key');
        $this->artisan('tisheh:key-init')->assertSuccessful();
        $this->assertSame($key, config('app.key'));
        $this->assertSame('1234567891', $u->fresh()->profile->national_id);
    }
}
