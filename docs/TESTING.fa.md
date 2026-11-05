# آزمون نسخه r0.4

## نتیجه تحویل

در اجرای ۵ نوامبر ۲۰۲۶ با PHP 8.2.12، **۱۳۸ تست feature با ۵۹۴ assertion روی SQLite پاس شدند**. آزمون کامل MariaDB پس از تغییرات این مرحله تکرار نشده است؛ گزارش‌های آرشیوی `test-sqlite.txt` و `test-mysql-driver.txt` به نسخه قبلی و ۱۲۷ تست/۵۰۹ assertion مربوط‌اند. Pint برای فایل‌های PHP تغییرکرده و کامپایل Blade نیز موفق بود. MySQL 8 مستقل، دستگاه Android، بار، نفوذ و همزمانی واقعی چند اتصال در این مرحله اجرا نشده‌اند.

```bash
composer install
php artisan test
php vendor/bin/pint --test
composer audit
php artisan view:cache
```

phpunit.xml از SQLite در حافظه استفاده می‌کند. برای تست mysql، متغیرهای DB را از shell خصوصی به دیتابیس جدا و خالی تست بدهید. **RefreshDatabase ممکن است جداول هدف را پاک کند؛ production هرگز هدف تست نباشد.**

## پوشش کلیدی

علاوه بر حساب و نقش و آگهی نسخه قبل: رندر تمام بخش‌های ادمین و منع غیرمدیر، ویرایش کاربر و منع جعل نقش/تأیید، حذف دسته استفاده‌شده، انتشار همراه اعلان، مخفی‌شدن آگهی حساب مسدود، ذخیره تنظیمات خالی و منع فعال‌سازی مالی، محتوای امن، پاسخ گزارش خصوصی، مالکیت اعلان، گفتگو یکتا و خصوصی، XSS پیام، snapshot قیمت سفارش، idempotency خریدار، حداقل/موجودی، منع آنلاین و خرید از خود، برگشت موجودی فقط یک بار، نقش/ترتیب وضعیت‌ها، حمل نامشخص، سبد پایدار، مالکیت سبد، تفکیک سفارش به فروشنده، checkout تکرارپذیر و اتمیک، آدرس خصوصی سفارش، CSV، ویرایش فروشگاه و ارقام فارسی تاریخ.

## اجرای آزمون مرورگر

اسکریپت از فرم‌های واقعی استفاده می‌کند و **پیام، گزارش و سفارش آزمایشی واقعی ایجاد می‌کند**. پاک‌سازی خودکار ندارد؛ تنها روی محیط disposable با DemoSeeder اجرا شود. برای اجرای مکرر مراقب موجودی داده نمونه و محدودیت تلاش ورود باشید.

۱. دیتابیس توسعه تازه، migrate/seed و حساب‌های نمونه:

```bash
php artisan migrate --seed
php artisan storage:link
umask 077
# رمز تصادفی نمونه فقط هنگام ایجاد حساب در اولین اجرا چاپ می‌شود
php artisan db:seed --class=DemoSeeder > /secure/demo-credentials.txt
```

۲. QA و سرور در shell جدا:

```bash
npm ci
npx playwright install --with-deps chromium
php artisan serve --host=0.0.0.0 --port=8000
```

۳. اجرا با رضایت صریح:

```bash
TISHEH_E2E_ALLOW_WRITE=1 \
TISHEH_CREDENTIALS=/secure/demo-credentials.txt \
TISHEH_BASE_URL=http://127.0.0.1:8000 \
npm run test:browser
```

گزارش پیش‌فرض `storage/logs/browser-results.txt` است؛ با TISHEH_BROWSER_REPORT می‌توان مسیر دیگری تعیین کرد. سناریو برای fixture DemoSeeder و محصول شماره ۱ آن طراحی شده است، نه دیتابیس ناشناس یا production. حساب‌های فایل credential باید متعلق به همان محیط باشند.

## سناریوهای مرورگر اجراشده

- ۱۳ مسیر ادمین با HTTP 200 و بدون بیرون‌زدگی افقی در عرض‌های ۳۹۰، ۷۶۸ و ۱۴۴۰.
- خانه، کاریابی، مصالح، راهنما، ثبت‌نام و جزئیات محصول در سه عرض؛ بدون تصویر شکسته.
- ذخیره تنظیمات با فیلد تماس اختیاری خالی.
- گفتگوی خریدار/فروشنده و پاسخ، مشاهده دوباره از دیتابیس؛ منع شخص ثالث با 403.
- سفارش مستقیم از ثبت تا تأیید، آماده ارسال، ارسال و تحویل توسط خریدار؛ وضعیت بانکی همچنان تأییدنشده.
- ثبت پشتیبانی توسط کارگر، پاسخ ادمین، پیگیری خصوصی و اعلان واقعی.
- پنل‌ها، پیام‌ها، سفارش‌ها، اعلان‌ها و گزارش‌های کاربران روی موبایل بدون overflow.
- هیچ exception جاوااسکریپتی در گردش آزموده‌شده ثبت نشد.

## قبل از تولید

تست رقابت موجودی/لغو در چند اتصال MySQL، restore backup واقعی، بار، MFA، ممیزی امنیت و سیاست داده لازم است. پس از اتصال قانونی مالی، suite اختصاصی callback، verify، refund و ledger باید اضافه شود. موفقیت تست‌های موجود، تضمین آمادگی تجاری نیست.


## گردش جدید OTP و پرداخت دمو

۲۵ تست `DemoIntegrationsTest` برای فرم و رمز کنار هم، کد تصادفی هش‌شده، انقضا، مصرف یک‌باره، nonce مرورگر، ارقام فارسی، شمارندهٔ خطای ماندگار، سقف تلاش، منع مدیر/حساب ناشناس/تعلیق/غیردمو، rate limit، ممنوعیت در production حتی با flag روشن، checkout سه‌نتیجه‌ای، عدم تغییر پرداخت واقعی، مالکیت، snapshot، expiry، idempotency، audit و جای خالی غیرقابل‌اجرای ملت اضافه شده است.

در محیط نمونهٔ disposable، `APP_ENV=local` و `TISHEH_DEMO_ENABLED=true` و `TISHEH_DEMO_OTP_ENABLED=true` را پیش از شروع سرور تنظیم کنید. با همان متغیرهای رضایت و credential:

```bash
TISHEH_E2E_ALLOW_WRITE=1 \
TISHEH_CREDENTIALS=/secure/demo-credentials.txt \
TISHEH_BASE_URL=http://127.0.0.1:8000 \
npm run test:browser:demo
```

این اسکریپت هم داده می‌نویسد و پاک‌سازی ندارد؛ گزارش پیش‌فرض `storage/logs/browser-demo-results.txt` و مسیر دلخواه با `TISHEH_BROWSER_REPORT`. برای اسکرین‌شات، `TISHEH_SCREENSHOT_DIR` اختیاری است. نتیجهٔ تحویل: ورود با چهار رقم از فرم واقعی، سه نتیجهٔ checkout با پرداخت تجاری تأییدنشده، منع شخص ثالث 403، monitor ادمین، بدون overflow در سه عرض و بدون exception جاوااسکریپت. `test-browser-demo.txt` گزارش جدید و `test-browser.txt` اجرای مجدد تمام سناریوهای قبلی است.

rate limit در QA نیز فعال است. بین اجرای مکرر اسکریپت‌ها مهلت محدودیت را رعایت کنید؛ پاک‌سازی cache فقط در دیتابیس/محیط disposable آزمون مجاز است، نه برای دورزدن محدودیت در تولید.


## r0.4 — نهایی‌سازی وب و cPanel

۳۰ تست FinalizationTest برای deadline/لغو یک‌باره، رویداد سیستم، عدم لغو سفارش تأییدشده/قدیمی، تأیید دیرهنگام، پذیرش مبلغ و منع جعل مالک/مبلغ، تغییر رمز/ابطال نشست/عدم flash، حذف امن تصویر/سقف ۸، fallback محدود تصویر، فیلتر بازه، in_review، سلامت ادمین و امنیت publisher اضافه شده است. `npm run test:browser:finalization` با همان متغیرهای رضایت، credential و URL اجرا می‌شود؛ fixture تصویر در tests/Fixtures است. این اسکریپت سفارش/محصول/حساب آزمایشی ایجاد می‌کند.

گزارش `test-browser-finalization.txt` و `test-cpanel-public.txt` همراه است؛ مورد دوم جداسازی public_html و launcher را با PHP development server، بدون symlink، و عدم دسترسی به secret/source اثبات می‌کند، نه نصب در cPanel/Apache واقعی. PHP اصلی آزمون 8.4 بود؛ lock با platform.php=8.2.0 حل شده، نه اینکه اجرای مستقل PHP 8.2 آزمایش شده باشد. بسته runtime بدون dev با DB تازه migrate/seed و HTTP جدا نیز بررسی می‌شود؛ جزئیات در گزارش نصب است.
