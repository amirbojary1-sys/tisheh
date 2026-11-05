# از اینجا شروع کنید — تیشه r0.4

**راهنمای اصلی: `docs/PROJECT-GUIDE.fa.pdf` (۲۶ صفحه)**؛ نسخه قابل ویرایش Word در `docs/PROJECT-GUIDE.fa.docx` و متن در `docs/PROJECT-GUIDE.fa.md` است.

## کدام بسته؟

- `tisheh-r0.4-cpanel.zip`: شامل سورس و vendor runtime آماده، بدون نیاز به Composer روی هاست برای همین نسخه؛ PHP/افزونه، دیتابیس، CLI و Cron همچنان لازم‌اند.
- `tisheh-r0.4-source.zip`: بدون vendor؛ پیش از راه‌اندازی باید `composer install --no-dev --prefer-dist --optimize-autoloader` اجرا شود.

## ترتیب نصب

۱. PHP وب و CLI سازگار 8.2+، MySQL/MariaDB، افزونه‌ها، SSL و Terminal/SSH یا همکاری پشتیبانی را آماده کنید.
۲. پوشه `tisheh` را خارج `public_html`، مثلاً `/home/CPUSER/apps/tisheh` قرار دهید. کل سورس و ZIP و .env نباید عمومی باشند.
۳. داخل پروژه `deploy/cpanel.env.example` را به `.env` کپی و دامنه و DB واقعی را تنظیم کنید. رمزها و APP_KEY در بسته وجود ندارند.
۴. فقط نصب اول: `php artisan key:generate`، سپس `php artisan migrate --force`، `php artisan db:seed --force` و `php artisan tisheh:admin`. اگر تأیید محیط production خواسته شد، تنها برای همین نصب جدید تأیید کنید. در ارتقا APP_KEY را عوض نکنید.
۵. Document Root را روی پوشه `public` بگذارید؛ اگر cPanel اجازه نمی‌دهد، از دستور زیر برای public_html جدا و خالی استفاده کنید:

```bash
php artisan tisheh:cpanel-publish --public-path=/home/CPUSER/public_html
```

۶. Cron را برای `schedule:run` هر دقیقه با مسیر واقعی PHP میزبان تنظیم کنید؛ وظیفه انقضای رزرو هر ۵ دقیقه اجرا می‌شود.
۷. مجوزهای storage و bootstrap/cache، cacheها، HTTPS و دستور بررسی نصب را کنترل کنید:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan tisheh:doctor --production --public-path=/home/CPUSER/public_html
```

در روش Document Root مستقیم، گزینه public-path را مطابق `.../tisheh/public` بدهید یا حذف کنید. دستور doctor جای بررسی PHP وب، TLS، Cron و restore نیست. CLI و دامنه ممکن است PHP متفاوت داشته باشند.

## هشدارهای مهم

- `migrate:fresh`، db:wipe یا key:generate هنگام ارتقا استفاده نشوند؛ `.env`، APP_KEY و storage اصلی حفظ شوند.
- سایت عمومی production با ورود رمز کار می‌کند. OTP و پرداخت دمو فقط در local/testing و با فعال‌سازی صریح مجازند؛ برای دمو محیط جدا و داده ساختگی لازم است.
- پرداخت واقعی، SMS واقعی، wallet، تسویه و Android در این تحویل نیستند. نتیجه موفق دمو پرداخت سفارش را paid نمی‌کند.
- رمز مدیر ثابت یا حساب نمونه تولیدی وجود ندارد. اولین مدیر را با فرمان تعاملی بسازید.
- این بسته در محیط آزمون نصب شده است، نه روی حساب cPanel شما. چک‌لیست پذیرش مقصد و عیب‌یابی در سند جامع آمده‌اند.
