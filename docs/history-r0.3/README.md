# TISHEH | تیشه — r0.3

**راهنمای کامل فارسی: [README.fa.md](README.fa.md)**

Laravel 12 / Blade construction jobs and materials marketplace. Reference-inspired black/gold RTL UI, dedicated admin, transactional direct orders, private messages, notifications and support.

New: session-bound four-digit **local demo OTP** and **demo checkout** with success/failure/cancel outcomes plus an admin monitor. Explicit opt-in is required (`TISHEH_DEMO_ENABLED=true` in local/testing); production always blocks demos. Demo login never verifies a real phone; demo payment never marks an order paid. Demo codes are restricted to allowlisted non-admin sample accounts.

Real SMS, banking, wallet, payout, refund and Android APK are NOT delivered. The Mellat adapter is an intentionally throwing extension stub, not an integration. See `docs/DEMO-INTEGRATIONS.fa.md` before extending providers.

97 feature tests / 395 assertions pass on SQLite and MariaDB (mysql driver). Chromium tests cover existing workflows and new demos at 390/768/1440 widths. Independent MySQL 8, load, penetration and Android tests have not run.

Install with Composer, configure private `.env`, migrate/seed, create storage link and first admin via `php artisan tisheh:admin`. Persian guide covers safe upgrade and demo setup.
