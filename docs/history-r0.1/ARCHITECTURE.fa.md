# معماری فنی تیشه r0.1

## ۱. مرز تحویل

این سند ابتدا معماری موجود را توضیح می‌دهد و سپس توسعه برنامه‌ریزی‌شده را مشخص می‌کند. جدول‌های سفارش، مالی، کیف پول و پیام‌ها هنوز ایجاد نشده‌اند؛ صرف وجود نام آنها در نقشه توسعه به معنی پیاده‌سازی نیست.

## ۲. ساختار موجود

```text
app/
  Console/Commands/       ایجاد مدیر و بازیابی اضطراری رمز
  Http/Controllers/      هماهنگ‌سازی درخواست/پاسخ
  Http/Requests/         اعتبارسنجی فارسی فرم‌های اصلی
  Http/Middleware/       فعال‌بودن حساب، مجوز، پراکسی و هدرها
  Models/                مدل‌های Eloquent و روابط
  Policies/              مالکیت درخواست کار و محصول
  Services/
    RegistrationService ثبت اتمیک حساب و پروفایل
    ListingService      ثبت آگهی و اعلام آمادگی تراکنشی
    UploadedImageService بازرمزگذاری تصویر به WebP
    AuditService        ثبت رویداد
  Support/Format.php     ارقام، ریال/تومان، تاریخ شمسی و وضعیت
bootstrap/app.php         زنجیره Middleware و مسیرها
config/tisheh.php         کلیدهای مالی غیرفعال و واحدهای فروش
resources/views/
  layouts/app.blade.php   پوسته فارسی، Header، Footer، منوی موبایل
  layouts/admin.blade.php پوسته مدیریت مستقل
  components/            آیکون، لوگو، فیلد، کارت و صفحه‌بندی
  auth/,jobs/,products/,admin/,errors/
public/
  css/app.css            CSS واقعی سایت و توکن‌های برند
  js/app.js              رفتار فرم نقش، جستجو و مقایسه
  fonts/                 فونت محلی و OFL
  images/                سربرگ و لوگوی SVG
routes/web.php            مسیرهای session-based وب
storage/app/public/       تصاویر عمومی؛ لینک با artisan storage:link
```

کد frontend نیازی به Vite ندارد. npm صرفاً برای آزمون مرورگر استفاده می‌شود. Controllerها به داده اعتبارسنجی‌شده دسترسی دارند؛ ثبت حساب، آگهی، تصویر و اعلام آمادگی در Service انجام می‌شود. منطق بررسی مدیریتی فعلی کوچک و تراکنشی است؛ با توسعه گردش تأیید و شکایت باید به سرویس Moderation اختصاصی منتقل شود.

## ۳. مسیر درخواست

```text
HTTP → web session + CSRF + محدودیت نرخ
     → احراز هویت و کنترل وضعیت حساب
     → Middleware مجوز نقش
     → FormRequest
     → Policy مالکیت رکورد
     → Controller → Service → Eloquent / تراکنش دیتابیس
     → AuditLog
     → Blade با escape پیش‌فرض
```

کارهای کنونی کوتاه و همزمان‌اند. زیرساخت queue استاندارد Laravel حاضر است، اما برای ارسال واقعی SMS/Push هیچ Job فعالی ایجاد نشده است. در مراحل بعد، ارسال اعلان باید پس از commit و با retry کنترل‌شده انجام شود، نه قبل از قطعی‌شدن تغییر دیتابیس.

## ۴. دیتابیس موجود

مبنای ساخت، Migrationها است. `schema-reference.sql` خروجی فقط ساختار از MariaDB محیط تحویل است؛ نباید به‌جای Migration روی production وارد شود.

| جدول | نقش و رابطه |
|---|---|
| users | موبایل یکتا، رمز هش‌شده، نقش، وضعیت حساب و زمان تأیید موبایل nullable |
| roles / permissions / permission_role | نقش واحد هر حساب و مجوزهای ماژولی |
| countries | IR فعال و IQ غیرفعال، ارز و پیش‌شماره؛ فعال‌بودن جدول به‌تنهایی سرویس کشوری راه نمی‌اندازد |
| user_profiles | رابطه ۱:۱ با کاربر؛ شهر، استان اختیاری، کشور، زبان، تخصص، کسب‌وکار و معرفی |
| seller_locations | رابطه ۱:۱ با فروشنده؛ آدرس عمومی؛ مختصات nullable و در این نسخه استفاده‌نشده |
| job_categories | دسته تخصص کاری |
| job_requests | متعلق به کارفرما و دسته؛ شهر قابل جستجو، آدرس خصوصی، دستمزد ریالی و وضعیت |
| job_applications | متعلق به درخواست و کارگر؛ قید unique روی دو شناسه برای جلوگیری از تکرار |
| material_categories | دسته مصالح |
| material_products | متعلق به فروشنده و دسته؛ قیمت ریالی integer، واحد، موجودی، حداقل سفارش و حمل nullable |
| product_images | چند تصویر عمومی برای هر محصول |
| audit_logs | عامل، رویداد، موضوع، متادیتا، IP و زمان؛ محدودیت append-only در مدل |
| settings | زیرساخت key/value JSON؛ رابط تنظیمات و مدل درآمد در این نسخه ندارد |
| sessions / cache / cache_locks | زیرساخت استاندارد Laravel |
| jobs / job_batches / failed_jobs | زیرساخت queue استاندارد؛ با جدول `job_requests` کاریابی اشتباه نشود |
| password_reset_tokens | جدول پیش‌فرض framework؛ بازیابی عمومی متصل نیست |
| migrations | تاریخچه تغییر ساختار |

روابط کلیدی:

```text
Role 1—N User 1—1 UserProfile
              1—1 SellerLocation
              1—N JobRequest N—1 JobCategory
                        1—N JobApplication N—1 User(worker)
              1—N MaterialProduct N—1 MaterialCategory
                        1—N ProductImage
```

مبالغ در BIGINT بدون اعشار و به ریال ثبت می‌شوند. تبدیل به تومان فقط لایه نمایش است؛ دهم تومان نیز حفظ می‌شود. هزینه حمل null به معنی نیاز به استعلام است، نه رایگان‌بودن. ارز فعلی IRR است؛ امکان ورودی ارز دیگر از فرم عمومی وجود ندارد.

## ۵. نقش‌ها و حریم خصوصی

- worker: جستجو، تماس پس از ورود و اعلام آمادگی برای فرصت منتشرشده.
- employer: ایجاد و ویرایش درخواست خود، مشاهده آدرس خصوصی، بررسی متقاضیان همان درخواست.
- seller: ایجاد و ویرایش محصولات خود، آپلود تصویر و غیرفعال‌کردن آگهی خود.
- admin: ورود به مدیریت، مشاهده آگهی‌های منتشرنشده برای بررسی، تأیید/رد و تغییر وضعیت کاربران غیرمدیر.
- نقش مدیر مجوز ساخت آگهی دیگران را به‌صورت ضمنی ندارد. نقش‌ها از فرم پروفایل قابل تغییر نیستند.
- موبایل در serialization مدل مخفی است؛ در view فقط در مسیرهای تعریف‌شده نشان داده می‌شود. این تنظیم به‌تنهایی جایگزین Policy نیست.
- آدرس پروژه عمومی نیست؛ آدرس فروشگاه برای معرفی محل فروش عمومی است.
- داده حساس هویتی یا شماره کارت بانکی اصلاً دریافت نمی‌شود.

## ۶. گردش وضعیت موجود

درخواست کار: `draft → pending → published / rejected`؛ مالک می‌تواند درخواست را `completed` یا `cancelled` کند. ویرایش مجدد نیازمند انتخاب draft یا ارسال دوباره برای بررسی است. تفکیک «در حال بررسی» از «در انتظار بررسی»، قواعد کامل انتقال وضعیت و تاریخچه مستقل آن برای نسخه بعد باقی است.

محصول: `draft → pending → published / rejected`؛ مالک می‌تواند آن را `inactive` کند. برای فعال‌سازی دوباره، ویرایش و ارسال برای بررسی لازم است. ویرایش قیمت نیز آگهی را دوباره به بررسی می‌برد؛ این سیاست اولیه محصول است و باید با کارفرما نهایی شود.

اعلام آمادگی: `pending → accepted / rejected` توسط مالک درخواست. تغییر وضعیت همراه با رویداد audit ثبت می‌شود؛ تاریخچه اختصاصی transition در این نسخه وجود ندارد.

اعلام آمادگی تکراری از دو لایه جلوگیری می‌شود: قفل سطر درخواست در DB transaction و قید unique در دیتابیس. آزمون چند اتصال همزمان هنوز انجام نشده است.

## ۷. امنیت موجود و محدودیت‌ها

کنترل‌های حاضر: CSRF، هش رمز، rotate نشست، cookie HttpOnly، SESSION_ENCRYPT، محدودیت ورود مبتنی بر IP و موبایل، مجوز نقش، Policy مالکیت، parameter binding، escape Blade، محدودیت حجم/MIME/ابعاد و re-encode تصویر.

هدرهای حاضر: CSP با script/style/font محلی، منع object، محدودیت base و form، nosniff، Referrer-Policy، Permissions-Policy و HSTS روی HTTPS. `CSP_FRAME_ANCESTORS` در پیش‌نمایش توسعه `*` است تا iframe محیط کار کند؛ در تولید باید `'self'` یا فهرست محدود دامنه‌های مجاز باشد. `TRUSTED_PROXIES=*` فقط برای پراکسی پیش‌نمایش است؛ در تولید IP پراکسی‌های واقعی را تعیین کنید.

پیش از تجاری‌شدن: ممیزی امنیتی مستقل، تست بار و همزمانی، MFA مدیر، سیاست حذف/نگهداری داده، لاگ مرکزی غیرقابل‌تغییر، redaction لاگ‌ها، کنترل abuse، سیاست قوی‌تر چرخه وضعیت و hardening سرور لازم است. محدودیت مدل AuditLog را می‌توان با SQL مستقیم دور زد؛ برای پول واقعی کافی نیست.

## ۸. بین‌المللی‌سازی

پایه حاضر: country/locale در پروفایل، کد ارز در داده قیمت، متغیرهای ترجمه Laravel، RTL و فونت محلی. نسخه فعال فقط فارسی و شماره موبایل ایران است.

توسعه بعدی: جداول countries/regions/cities با کلید خارجی، ترجمه `ar`، قالب تاریخ و پول بر اساس کشور، شماره E.164 و ارائه‌دهنده پرداخت مستقل هر کشور. رشته‌های رابط فعلی هنوز همگی به فایل ترجمه منتقل نشده‌اند. عراق در جدول غیرفعال است؛ هیچ پرداخت، مجوز یا فعالیت تجاری در عراق از این زیرساخت نتیجه نمی‌شود.

## ۹. مدل داده مراحل آینده — هنوز ایجاد نشده

- پروفایل‌های تخصصی worker/employer/seller در صورت گسترش فیلدهای دامنه، با مهاجرت از user_profiles؛ نه ایجاد جدول خالی صرفاً برای شمارش خروجی.
- شهر و استان نرمال‌شده، موقعیت‌های چندگانه فروشنده و ایندکس جغرافیایی.
- material_orders، items با snapshot مشخصات/قیمت و وضعیت، order_status_events و رزرو موجودی.
- payment_transactions، payment_attempts، provider_events، refunds و idempotency_keys.
- ledger_accounts، ledger_journals، ledger_entries به‌عنوان دفترکل دوطرفه؛ wallet_accounts و withdrawal_requests فقط پس از تأیید حقوقی/ارائه‌دهنده.
- conversations، conversation_participants، messages، notifications، device_tokens.
- reports، complaints، complaint_events، reviews با شرط سابقه معتبر.
- subscriptions، plans، publication_fees، platform_revenue؛ تفکیک طلب اشخاص از درآمد تیشه.

برای دامنه مالی به `PAYMENTS.fa.md` مراجعه کنید. وجود این طرح، به معنی ارائه سرویس مالی نیست.
