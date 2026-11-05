# TISHEH | تیشه — Network 1

> **Current delivery — Network 1:** multi-role accounts, optional encrypted identity fields, moderated professional directory and private portfolio images. **185 tests / 887 assertions pass on each of SQLite and MariaDB (mysql driver)**; 207 planned browser visits/checks. Five Symfony dependencies security-patched; Composer audit reports no known advisories. Fresh empty-database SQL and migration upgrade included. [Current Persian delivery/SQL/upgrade guide](docs/NETWORK-DELIVERY.fa.md). Earlier counts below are historical.

> **Previous UI revision (2026-11-05):** mobile-first black/gold redesign on GitHub baseline `3712167`, with real registration/job wizards and unchanged storage schema. **146 tests / 691 assertions passed on SQLite**; then-current browser checks covered 126 workflow visits plus a 24-visit public responsive matrix. Earlier engine/test counts below are historical. This UI revision has not been re-tested on MySQL/MariaDB or real cPanel hosting. See [historical UI delivery notes](docs/CRAFT-DESIGN.fa.md).

**راهنمای کامل فارسی: [README.fa.md](README.fa.md)**

Laravel 12 / Blade construction jobs and materials marketplace. Reference-inspired black/gold RTL UI, dedicated admin, transactional direct orders, private messages, notifications and support.

New: session-bound four-digit **local demo OTP** and **demo checkout** with success/failure/cancel outcomes plus an admin monitor. Explicit opt-in is required (`TISHEH_DEMO_ENABLED=true` in local/testing); production always blocks demos. Demo login never verifies a real phone; demo payment never marks an order paid. Demo codes are restricted to allowlisted non-admin sample accounts.

Real SMS, banking, wallet, payout, refund and Android APK are NOT delivered. The Mellat adapter is an intentionally throwing extension stub, not an integration. See `docs/DEMO-INTEGRATIONS.fa.md` before extending providers.

127 feature tests / 509 assertions pass on SQLite and MariaDB (mysql driver). Chromium tests cover existing workflows and new demos at 390/768/1440 widths. Independent MySQL 8, load, penetration and Android tests have not run.

Install with Composer, configure private `.env`, migrate/seed, create storage link and first admin via `php artisan tisheh:admin`. Persian guide covers safe upgrade and demo setup.

## cPanel release
Read `docs/PROJECT-GUIDE.fa.md` first. Runtime ZIP includes no-dev vendor dependencies. Source ZIP does not. Locked dependencies now resolve for PHP 8.2; tests actually ran on PHP 8.4. Never put private source or .env under document root. Use `deploy/cpanel.env.example`, `tisheh:cpanel-publish`, and `tisheh:doctor`. Configure cron for reservation expiration. New: buyer acceptance of changed shipping quotes, password rotation/session revocation, scoped image deletion, job filters, in_review moderation and admin system monitor. Actual hosting/Apache and domain deployment remain destination acceptance tasks.
