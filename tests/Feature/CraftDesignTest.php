<?php

namespace Tests\Feature;

use App\Models\JobCategory;
use App\Models\JobRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class CraftDesignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function account(string $role): User
    {
        $user = new User(['name' => 'آزمون', 'last_name' => 'قالب', 'mobile' => '09'.str_pad((string) random_int(1, 999999999), 9, '0', STR_PAD_LEFT), 'password' => 'DesignTest2026']);
        $user->role_id = Role::where('name', $role)->sole()->id;
        $user->save();
        $user->profile()->create(['city' => 'تهران']);

        return $user;
    }

    private function publishedJob(): JobRequest
    {
        return JobRequest::create(['user_id' => $this->account('employer')->id, 'job_category_id' => JobCategory::first()->id, 'title' => 'درخواست آزمایشی قالب', 'description' => str_repeat('توضیحات آزمایشی پروژه. ', 4), 'city' => 'تهران', 'address' => 'نشانی خصوصی آزمایشی', 'workers_count' => 2, 'start_date' => now()->addDays(2), 'duration_days' => 20, 'wage' => null, 'payment_type' => 'project', 'status' => 'published']);
    }

    public function test_public_pages_keep_rtl_and_load_shared_local_design_assets(): void
    {
        foreach (['/', '/login', '/register', '/jobs', '/materials', '/terms', '/pages/help'] as $path) {
            $this->get($path)->assertOk()->assertSee('dir="rtl"', false)->assertSee('css/craft.css', false)->assertSee('js/craft.js', false);
        }
    }

    public function test_registration_retains_all_three_roles_and_existing_backend_fields(): void
    {
        $response = $this->get('/register')->assertOk()->assertSee('data-registration-wizard', false);
        foreach (['worker', 'employer', 'seller'] as $role) {
            $response->assertSee('value="'.$role.'"', false);
        }
        foreach (['name', 'last_name', 'mobile', 'password', 'password_confirmation', 'city', 'specialty', 'business_name', 'address', 'terms', '_token'] as $field) {
            $response->assertSee('name="'.$field.'"', false);
        }
        $response->assertSee('data-onboarding-step="0"', false)->assertSee('data-onboarding-step="1"', false)->assertSee('data-onboarding-step="2"', false);
    }

    public function test_job_wizard_retains_draft_review_csrf_and_permission_checks(): void
    {
        $this->actingAs($this->account('worker'))->get('/jobs/create')->assertForbidden();
        $response = $this->actingAs($this->account('employer'))->get('/jobs/create')->assertOk()->assertSee('data-multistep-form', false);
        foreach (['title', 'category_id', 'description', 'skills', 'city', 'address', 'workers_count', 'duration_days', 'start_date', 'wage', 'payment_type', '_token'] as $field) {
            $response->assertSee('name="'.$field.'"', false);
        }
        $response->assertSee('value="pending"', false)->assertSee('value="draft"', false);
    }

    public function test_only_exact_demo_image_paths_use_illustrative_assets(): void
    {
        foreach (['cement', 'steel', 'brick', 'sand'] as $material) {
            $html = Blade::render('<x-product-photo :image="$image" alt="آزمون" />', ['image' => (object) ['path' => 'samples/'.$material.'.svg']]);
            $this->assertStringContainsString('/images/materials/'.$material.'.webp', $html);
            $this->assertStringContainsString('تصویر نمایشی، نه عکس کالای واقعی', $html);
        }
    }

    public function test_real_seller_photo_paths_are_not_replaced_or_mislabeled(): void
    {
        $html = Blade::render('<x-product-photo :image="$image" alt="عکس فروشنده" class="detail-image" />', ['image' => (object) ['path' => 'products/real-seller-photo.webp']]);
        $this->assertStringContainsString('/storage/products/real-seller-photo.webp', $html);
        $this->assertStringContainsString('class="detail-image"', $html);
        $this->assertStringNotContainsString('تصویر نمایشی', $html);
        $other = Blade::render('<x-product-photo :image="$image" />', ['image' => (object) ['path' => 'samples/another-photo.svg']]);
        $this->assertStringContainsString('/storage/samples/another-photo.svg', $other);
    }

    public function test_existing_application_displays_actual_state_instead_of_duplicate_form(): void
    {
        $worker = $this->account('worker');
        $job = $this->publishedJob();
        $this->actingAs($worker)->get('/jobs/'.$job->id)->assertOk()->assertSee('name="message"', false);
        $this->from('/jobs/'.$job->id)->post('/jobs/'.$job->id.'/apply', ['message' => 'معرفی آزمایشی برای بررسی حفظ عملکرد درخواست'])->assertRedirect('/jobs/'.$job->id)->assertSessionHas('success');
        $job->applications()->where('user_id', $worker->id)->update(['status' => 'accepted']);
        $this->get('/jobs/'.$job->id)->assertOk()->assertSee('اعلام آمادگی شما ثبت شده است')->assertSee('پذیرفته‌شده')->assertDontSee('name="message"', false);
    }

    public function test_application_display_is_scoped_to_current_user(): void
    {
        $job = $this->publishedJob();
        $job->applications()->create(['user_id' => $this->account('worker')->id, 'message' => 'متن خصوصی آزمایشی متقاضی دیگر', 'status' => 'accepted']);
        $this->actingAs($this->account('worker'))->get('/jobs/'.$job->id)->assertOk()->assertSee('name="message"', false)->assertDontSee('اعلام آمادگی شما ثبت شده است')->assertDontSee('متن خصوصی آزمایشی متقاضی دیگر');
    }

    public function test_authenticated_account_and_admin_use_shared_design_without_weakening_csp(): void
    {
        $this->actingAs($this->account('worker'))->get('/dashboard')->assertOk()->assertSee('css/craft.css', false);
        $response = $this->actingAs($this->account('admin'))->get('/admin')->assertOk()->assertSee('css/craft.css', false)->assertSee('craft-admin', false);
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringContainsString("style-src 'self'", $csp);
        $this->assertStringNotContainsString('unsafe-inline', $csp);
    }
}
