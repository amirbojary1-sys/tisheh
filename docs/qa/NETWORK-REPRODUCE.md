# Reproduce Network 1 checks

**Use disposable test databases and isolated demo accounts only.** `RefreshDatabase` can remove tables/data in the configured test database. Never point PHPUnit or browser write scenarios at production, a valuable database, or actual user accounts. This release does not contain demo passwords or a private MySQL test configuration.

## PHPUnit

```bash
composer install
# First complete initial setup on an isolated local instance, including .env and a fresh key.
php artisan test
```

The checked-in `phpunit.xml` selects an in-memory SQLite test database. For MySQL, make a private copy of that configuration with an **entirely separate disposable database** and its own restricted user. Set test environment variables with `force="true"` so your ordinary `.env` cannot accidentally select the deployment database. Use your isolated local instance’s freshly generated application key, or configure a separate private test-only `APP_KEY`; never copy a production key just to run tests. use `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`. Run `vendor/bin/phpunit --configuration /private/path/phpunit-mysql.xml`. Do not commit that credential file.

## Browser

```bash
npm ci
npx playwright install chromium
```

Start an isolated demo instance and provision fixture accounts with `php artisan db:seed --class=DemoSeeder`; see `DEMO-INTEGRATIONS.fa.md`. DemoSeeder refuses non-local/non-testing environments; execute it only by explicit choice on the isolated database. Keep the resulting credentials private and outside the source directory.

```bash
BASE_URL=http://127.0.0.1:8001 \
ALLOW_DEMO_WRITES=TISHEH-DEMO-ONLY \
DEMO_CREDENTIALS_FILE=/private/path/demo-credentials.txt \
npm run test:network

BASE_URL=http://127.0.0.1:8001 \
ALLOW_DEMO_WRITES=TISHEH-DEMO-ONLY \
DEMO_CREDENTIALS_FILE=/private/path/demo-credentials.txt \
npm run test:craft

BASE_URL=http://127.0.0.1:8001 npm run test:responsive
```

`test:network` is the portable new network workflow; `test:craft` and `test:responsive` preserve the UI workflow/responsiveness scenarios. Browser tests create demo data and leave it in the isolated environment for inspection. They are not readonly health checks. Screenshots default to the excluded `tests/browser/screenshots` folder; `QA_SCREENSHOTS` can select another directory.

Evidence files record individual successful local runs, not proof of real payment/SMS, deployment, legal compliance, penetration testing or real-device testing. Browser result counts are 57 network + 126 existing + 24 public checks. HTTP smoke checks on extracted source are additional and not counted as browser visits.

The archive smoke log was captured from the packaged source with a fresh production dependency install, a private new application key and the independently restored clean SQL database. Key values and database credentials are not in the log or archive. The only subsequent source changes are documentation, npm script aliases and a PHPUnit safety setting that forces the checked-in SQLite test configuration instead of inherited shell database variables; application PHP/Blade/CSS/JS and locked dependencies are the same tested source.
