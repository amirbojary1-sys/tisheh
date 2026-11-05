# راهنمای استقرار — r0.4

برای cPanel سند اصلی و گام‌به‌گام **PROJECT-GUIDE.fa.md** را بخوانید. اطلاعات عمومی Linux زیر مکمل آن است. بسته cPanel شامل vendor runtime است؛ source به Composer نیاز دارد. راهنما با doctor و publisher جدید تکمیل شده است.

# استقرار، نگهداری و پشتیبان‌گیری

این راهنما برای استقرار مرحله‌ای است. در این تحویل استقرار روی دامنه تجاری، نصب TLS، تنظیم DNS و آزمون restore سرور واقعی انجام نشده‌اند. `artisan serve` صرفاً سرور توسعه است، نه سرور production.

## ۱. محیط‌ها و پیش‌نیازها

- development، staging و production دیتابیس، APP_KEY، secret و حساب سرویس مستقل داشته باشند.
- PHP-FPM سازگار با Laravel 12، Nginx، Composer و MySQL 8؛ GD با پشتیبانی WebP و افزونه‌های ذکرشده در README.
- DocumentRoot فقط `public/`؛ `.env`، `vendor` و `storage` خصوصی مستقیماً سرو نشوند.
- کاربر برنامه حداقل دسترسی لازم دیتابیس را داشته باشد. اجرای Migration در انتشار با حساب محدود و مجوزهای لازم جدا بررسی شود.
- برای تولید، داده آزمایشی و فایل حساب‌های نمونه نباید منتقل شوند.

## ۲. ساخت و انتشار

در release directory جدید، بدون تعویض پوشه فعال تا پایان صحت‌سنجی:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
# .env امن و APP_KEY موجود را از secret store تهیه کنید
php artisan migrate --force
php artisan db:seed --force  # فقط DatabaseSeeder؛ ساخت نقش و دسته، نه داده نمونه
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

برای اولین نصب، `php artisan key:generate` یک بار پیش از انتشار لازم است. در انتشارهای بعد **کلید را دوباره تولید نکنید**؛ نشست‌ها و داده رمزنگاری‌شده وابسته به آن‌اند. در نسخه فعلی `composer install --no-dev` برای اجرای سایت کافی است؛ PHPUnit و Pint در محیط QA به وابستگی dev نیاز دارند.

`storage` و `bootstrap/cache` برای کاربر PHP-FPM قابل نوشتن باشند؛ از `chmod 777` استفاده نشود. `storage/app/public` در انتشارهای بعد shared و پایدار باشد. پوشه upload نباید امکان اجرای PHP داشته باشد.

## ۳. تنظیمات ضروری تولید

```dotenv
APP_ENV=production
TISHEH_DEMO_ENABLED=false
TISHEH_DEMO_OTP_ENABLED=false
DEMO_OTP_MOBILES=
APP_DEBUG=false
APP_URL=https://YOUR_DOMAIN
APP_LOCALE=fa
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
LOG_LEVEL=warning
TRUSTED_PROXIES=IP_OF_YOUR_REAL_REVERSE_PROXY
CSP_FRAME_ANCESTORS="'self'"
```

اگر reverse proxy ندارید، `TRUSTED_PROXIES` خالی بماند. اعتماد به `*` فقط در محیط پیش‌نمایش کنترل‌شده مجاز است؛ در production باعث اعتماد به headerهای جعل‌شدنی می‌شود. CORS wildcard لازم نیست؛ این سایت session-based و هم‌مبدأ است.

HTTPS معتبر، redirect از HTTP و تمدید خودکار certificate الزامی است. هدر HSTS در پاسخ HTTPS برنامه افزوده می‌شود. تنظیم `SESSION_SECURE_COOKIE=false` فقط برای توسعه HTTP محلی است. به دلیل embedding پیش‌نمایش، محیط توسعه frame-ancestors باز دارد؛ تولید نباید همین مقدار را به ارث ببرد.

مقادیر دامنه و مسیر زیر نمونه‌اند و باید جایگزین شوند؛ این فایل پیکربندی در این محیط اجرا نشده است:

```nginx
server {
    listen 443 ssl;
    server_name YOUR_DOMAIN;
    root /srv/tisheh/current/public;
    index index.php;
    # ssl_certificate / ssl_certificate_key از مدیریت گواهی واقعی
    client_max_body_size 6m;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location = /index.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root/index.php;
        fastcgi_param HTTPS on;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }
    location ~ \.php$ { return 404; }
    location ~ /\.(?!well-known).* { deny all; }
    location /storage/ {
        try_files $uri =404;
        add_header X-Content-Type-Options nosniff always;
    }
}
```

با location دقیق index.php، فایل PHP آپلودی اجرا نمی‌شود؛ backend نیز فقط raster معتبر را به WebP تبدیل می‌کند. سقف `upload_max_filesize=4M`، `post_max_size` حدود 6M و حافظه کافی برای تصویر ۴۰۹۶×۴۰۹۶ در PHP-FPM تعیین شود. خطای 413 وب‌سرور هم باید پیام مناسب فارسی داشته باشد.

## ۴. آزمون انتشار

- `/up` وضعیت سلامت framework است؛ به‌تنهایی صحت تمام دیتابیس/سرویس‌ها را تضمین نمی‌کند. probe جدا برای دیتابیس و ارائه‌دهندگان آینده لازم است.
- خانه، ورود، ثبت درخواست نقش آزمایشی، ثبت مصالح، upload و بررسی مدیر در staging آزمایش شوند.
- درخواست بدون CSRF رد، کاربر غیرمدیر از `/admin` منع و رکورد خصوصی دیگران غیرقابل مشاهده باشد.
- کوکی Secure/HttpOnly، CSP، نبود stack trace و نبود آگهی نمونه در production بررسی شود.
- قبل از تغییر schema پشتیبان تهیه و سازگاری rollback نسخه کد با schema جدید بررسی شود؛ migration rollback کورکورانه اجرا نشود.

## ۵. صف و زمان‌بندی در مراحل بعد

این نسخه Job پیامک/اعلان بیرونی فعال ندارد؛ وظیفه آزادسازی رزرو موجودی فعال است و schedule:run باید هر دقیقه اجرا شود. وقتی adapterهای واقعی اضافه شدند، Worker تحت supervisor/systemd با timeout/retry متناسب و `queue:restart` هنگام انتشار لازم است. زمان‌بندی `schedule:run` برای reconciliation یا آزادسازی reservation تنها پس از پیاده‌سازی و آزمون وظیفه واقعی اضافه شود. افزودن cron خالی به معنی پیاده‌سازی reconciliation نیست.

## ۶. پشتیبان‌گیری امن

این رویه باید روی محیط واقعی با policy نگهداری تأییدشده اجرا و restore آن آزموده شود؛ در این تحویل backup production انجام نشده است.

۱. فایل client credential با permission `0600`، خارج از webroot و Git بسازید:

```ini
[client]
host=127.0.0.1
user=YOUR_BACKUP_USER
password=YOUR_BACKUP_PASSWORD
```

۲. با ابزار مناسب سرور MySQL، dump سازگار و تراکنشی بسازید. رمز را در آرگومان command line نگذارید:

```bash
umask 077
mysqldump --defaults-extra-file=/secure/mysql-backup.cnf \
  --single-transaction --no-tablespaces --routines --triggers \
  tisheh | gzip > /secure/staging/tisheh-YYYYMMDD.sql.gz
```

مسیر staging خصوصی و رمزنگاری دیسک باشد؛ فایل plaintext فشرده حاوی داده شخصی است و نباید در public یا workspace اشتراکی نگه داشته شود. سپس با age/GPG و کلید عمومی مسئول backup رمزنگاری و نسخه staging غیرضروری طبق رویه امن حذف شود.

۳. `storage/app/public`، تنظیمات deploy و **APP_KEY اصلی** نیز نسخه پشتیبان رمزنگاری‌شده جدا داشته باشند. از گم‌شدن کلید یا انتقال آن همراه با فایل عمومی سورس جلوگیری شود.
۴. نسخه رمزنگاری‌شده خارج از همان سرور، با دسترسی حداقلی و سیاست retention مصوب نگهداری شود. اعتبار dump و checksum بررسی شود.
۵. RPO/RTO را کسب‌وکار تعیین کند؛ پیشنهاد شروع، backup روزانه و آزمون restore ماهانه است، نه تضمین تحقق آن در نسخه فعلی.

## ۷. بازیابی

- در محیط جدا و فاقد دسترسی کاربر واقعی، دیتابیس خالی هم‌نسخه بسازید.
- backup را با کلید مجاز به مسیر خصوصی decrypt کنید؛ سپس به DB جدا وارد کنید:

```bash
gunzip -c /secure/restore/tisheh.sql.gz | mysql \
  --defaults-extra-file=/secure/mysql-restore.cnf tisheh_restore
```

- فایل‌ها و APP_KEY متناظر را برگردانید. `.env` باید به DB بازیابی اشاره کند، نه production.
- شمارش کاربران/آگهی‌ها، محدودیت‌های FK، تصاویر، ورود آزمایشی و audit را کنترل کنید. در مراحل مالی، توازن دفترکل و reconciliation provider الزامی خواهد بود.
- زمان واقعی restore و نتیجه را ثبت کنید. بدون restore موفق، داشتن فایل backup کافی نیست.

## ۸. پایش و محدودیت‌های انتشار

خطاهای 5xx، شکست ورود، queue آینده، اشغال دیسک، کندی DB و backup monitor شوند. لاگ‌ها نباید رمز، OTP، secret یا اطلاعات کارت داشته باشند. ممکن است QueryException متادیتای داده ورودی را در log ثبت کند؛ دسترسی و redaction مرکزی پیش از production لازم است. ممیزی امنیت، MFA مدیر، حفاظت DDoS و سیاست حذف/صادرات داده هنوز تکمیل نشده‌اند.


## دمو r0.3

فرم چهاررقمی و درگاه دمو فقط در local/testing و با flag روشن کار می‌کنند؛ در production عمداً 503 هستند. در انتشار تولیدی flag خاموش، allowlist خالی و داده نمونه حذف‌شده باشد. هرگز برای دسترسی عمومی دمو، APP_ENV سرور واقعی را local نکنید. اتصال آیندهٔ واقعی باید مسیر مستقل و آزموده‌شده بدون نمایش کد و بدون fallback به دمو داشته باشد؛ مستند DEMO-INTEGRATIONS.fa.md را دنبال کنید.
