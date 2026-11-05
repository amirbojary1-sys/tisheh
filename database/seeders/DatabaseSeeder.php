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
        foreach (['jobs.create', 'products.create', 'admin.access'] as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }
        foreach (['worker' => 'کارگر و استادکار', 'employer' => 'کارفرما و پیمانکار', 'seller' => 'فروشنده مصالح', 'admin' => 'مدیر سامانه'] as $name => $label) {
            $r = Role::firstOrCreate(['name' => $name], ['label' => $label]);
            $p = match ($name) {
                'employer' => ['jobs.create'],'seller' => ['products.create'],'admin' => ['admin.access'],default => []
            };
            $r->permissions()->sync(Permission::whereIn('name', $p)->pluck('id'));
        }
        foreach ([['IR', 'ایران', 'IRR', '+98', true], ['IQ', 'عراق', 'IQD', '+964', false]] as [$code,$name,$currency,$dial,$enabled]) {
            DB::table('countries')->updateOrInsert(['code' => $code], ['name' => $name, 'currency' => $currency, 'dial_code' => $dial, 'enabled' => $enabled]);
        }
        foreach (['masonry' => 'بنایی و سفت‌کاری', 'electric' => 'برق‌کاری', 'plumbing' => 'تأسیسات و لوله‌کشی', 'welding' => 'جوشکاری', 'painting' => 'نقاشی ساختمان', 'general' => 'کارگر ساده', 'tiling' => 'کاشی و سرامیک'] as $slug => $name) {
            JobCategory::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }
        foreach (['cement' => 'سیمان و گچ', 'steel' => 'آهن و فولاد', 'brick' => 'آجر و بلوک', 'tile' => 'کاشی و سرامیک', 'sand' => 'شن و ماسه', 'tools' => 'ابزار و تجهیزات'] as $slug => $name) {
            MaterialCategory::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }
        $this->call(ContentSeeder::class);
    }
}
