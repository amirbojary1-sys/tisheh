<?php

namespace Database\Seeders;

use App\Models\JobCategory;
use App\Models\JobRequest;
use App\Models\MaterialCategory;
use App\Models\MaterialProduct;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo seeding is forbidden outside local/testing.');
        }
        DB::transaction(function () {
            $users = [];
            foreach (['admin' => 'مدیر', 'employer' => 'کارفرما', 'worker' => 'استادکار', 'seller' => 'مصالح‌فروش'] as $i => $name) {
                $phone = ['admin' => '09000000001', 'employer' => '09000000002', 'worker' => '09000000003', 'seller' => '09000000004'][$i];
                $user = User::where('mobile', $phone)->first();
                if (! $user) {
                    $password = Str::password(20);
                    $user = new User(['name' => $name, 'last_name' => 'نمونه', 'mobile' => $phone, 'password' => $password]);
                    $user->role_id = Role::where('name', $i)->sole()->id;
                    $user->save();
                    $user->profile()->create(['city' => 'تهران', 'specialty' => $i === 'worker' ? 'برق‌کاری' : null, 'business_name' => $i === 'seller' ? 'بازرگانی سازه (نمونه)' : null]);
                    if ($i === 'seller') {
                        $user->location()->create(['name' => 'بازرگانی سازه (نمونه)', 'city' => 'تهران', 'address' => 'آدرس آزمایشی؛ فروشگاه واقعی نیست']);
                    }$this->command?->line($i.' | '.$phone.' | '.$password);
                }
                $users[$i] = $user;
            }
            foreach ([
                ['برق‌کار ماهر برای پروژه مسکونی', 'electric', 'تهران، منطقه ۵', 3, 18000000, 'daily'],
                ['استادکار بنایی و دیوارچینی', 'masonry', 'کرج', 4, 22000000, 'daily'],
                ['جوشکار اسکلت فلزی ساختمان', 'welding', 'تهران', 2, null, 'project'],
                ['نقاش ساختمان با سابقه کار', 'painting', 'اصفهان', 2, 16000000, 'daily'],
                ['نیروی تأسیسات و لوله‌کشی', 'plumbing', 'تهران', 3, 19000000, 'daily'],
                ['کارگر ساده برای پروژه تجاری', 'general', 'شیراز', 6, 12000000, 'daily'],
            ] as [$title,$cat,$city,$count,$wage,$payment]) {
                JobRequest::firstOrCreate(['title' => $title, 'user_id' => $users['employer']->id], ['job_category_id' => JobCategory::where('slug', $cat)->sole()->id, 'description' => 'این یک آگهی نمونه برای آزمایش سامانه تیشه است و پیشنهاد شغلی واقعی نیست. به نیروی متعهد و آشنا با اصول ایمنی برای همکاری در پروژه ساختمانی نیاز داریم.', 'skills' => 'تجربه مرتبط، مسئولیت‌پذیری و آشنایی با ایمنی کارگاه', 'city' => $city, 'address' => 'آدرس خصوصی آزمایشی', 'workers_count' => $count, 'start_date' => now()->addDays(7), 'duration_days' => 60, 'wage' => $wage, 'payment_type' => $payment, 'status' => 'published']);
            }
            foreach ([
                ['سیمان تیپ ۲، پاکت ۵۰ کیلویی', 'cement', 'سیمان تهران', 1250000, 'bag', 2500, 10, 15000000, 'cement.svg'],
                ['میلگرد آجدار A3 سایز ۱۶', 'steel', 'ذوب‌آهن اصفهان', 365000000, 'ton', 80, 1, 25000000, 'steel.svg'],
                ['آجر سفالی تیغه ۱۵ سانت', 'brick', 'سفال البرز', 105000, 'piece', 12000, 500, null, 'brick.svg'],
                ['ماسه شسته دوبار شور', 'sand', 'معادن شهریار', 3400000, 'ton', 300, 5, 18000000, 'sand.svg'],
                ['سیمان تیپ ۲، پاکت ۵۰ کیلویی', 'cement', 'سیمان آبیک', 1190000, 'bag', 1200, 20, 19000000, 'cement.svg'],
                ['آجر سفالی تیغه ۱۰ سانت', 'brick', 'سفال نمونه', 88000, 'piece', 8000, 500, 12000000, 'brick.svg'],
            ] as [$name,$cat,$brand,$price,$unit,$stock,$min,$shipping,$image]) {
                $p = MaterialProduct::firstOrCreate(['name' => $name, 'brand' => $brand, 'user_id' => $users['seller']->id], ['material_category_id' => MaterialCategory::where('slug', $cat)->sole()->id, 'specifications' => 'این محصول، موجودی و قیمت، داده آزمایشی هستند و پیشنهاد فروش واقعی نیستند. مشخصات فنی برای نمایش گردش کار مقایسه ثبت شده‌اند. تصاویر شماتیک هستند.', 'price' => $price, 'unit' => $unit, 'stock' => $stock, 'minimum_order' => $min, 'shipping_cost' => $shipping, 'sale_terms' => 'صرفاً برای تست؛ معامله واقعی انجام نشود. حمل پس از هماهنگی و استعلام نهایی.', 'city' => 'تهران', 'status' => 'published']);
                $p->images()->firstOrCreate(['path' => 'samples/'.$image]);
            }
            app(AuditService::class)->record('demo.seeded', $users['admin']);
        });
    }
}
