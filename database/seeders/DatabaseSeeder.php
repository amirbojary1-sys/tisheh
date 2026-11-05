<?php

namespace Database\Seeders;

use App\Models\JobCategory;
use App\Models\MaterialCategory;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['jobs.create', 'products.create', 'admin.access', 'identity.review'] as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }
        foreach (['worker' => 'متخصص و نیروی کار', 'employer' => 'کارفرما و پیمانکار', 'seller' => 'فروشنده مصالح', 'admin' => 'مدیر سامانه'] as $name => $label) {
            $r = Role::firstOrCreate(['name' => $name], ['label' => $label]);
            $p = match ($name) {
                'employer' => ['jobs.create'],'seller' => ['products.create'],'admin' => ['admin.access', 'identity.review'],default => []
            };
            $r->permissions()->sync(Permission::whereIn('name', $p)->pluck('id'));
        }
        Role::where('name', 'worker')->where('label', 'کارگر و استادکار')->update(['label' => 'متخصص و نیروی کار']);
        foreach ([['IR', 'ایران', 'IRR', '+98', true], ['IQ', 'عراق', 'IQD', '+964', false]] as [$code,$name,$currency,$dial,$enabled]) {
            DB::table('countries')->updateOrInsert(['code' => $code], ['name' => $name, 'currency' => $currency, 'dial_code' => $dial, 'enabled' => $enabled]);
        }
        foreach (['masonry' => 'بنایی و سفت‌کاری', 'electric' => 'برق‌کاری', 'plumbing' => 'تأسیسات و لوله‌کشی', 'welding' => 'جوشکاری', 'painting' => 'نقاشی ساختمان', 'general' => 'کارگر ساده', 'tiling' => 'کاشی و سرامیک'] as $slug => $name) {
            JobCategory::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }
        foreach (['cement' => 'سیمان و گچ', 'steel' => 'آهن و فولاد', 'brick' => 'آجر و بلوک', 'tile' => 'کاشی و سرامیک', 'sand' => 'شن و ماسه', 'tools' => 'ابزار و تجهیزات'] as $slug => $name) {
            MaterialCategory::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }
        // Add missing construction disciplines without renaming or replacing existing categories.
        foreach (['civil-engineering' => 'مهندسی عمران', 'architecture' => 'معماری و طراحی', 'surveying' => 'نقشه‌برداری', 'hvac' => 'تهویه و سرمایش', 'carpentry' => 'نجاری و کابینت', 'elevator' => 'آسانسور', 'roofing' => 'سقف و شیروانی', 'insulation' => 'عایق‌کاری', 'demolition' => 'تخریب و بازسازی', 'project-management' => 'مدیریت و نظارت پروژه', 'landscaping' => 'محوطه‌سازی'] as $slug => $name) {
            if (! JobCategory::where('slug', $slug)->orWhere('name', $name)->exists()) {
                JobCategory::create(['slug' => $slug, 'name' => $name]);
            }
        }
        $this->call(ContentSeeder::class);
    }
}
