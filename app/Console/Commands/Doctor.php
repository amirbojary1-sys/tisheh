<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Doctor extends Command
{
    protected $signature = 'tisheh:doctor {--production : Enforce production safety requirements} {--public-path= : Actual document root if separate, e.g. public_html}';

    protected $description = 'Read-only installation checks without printing credentials';

    public function handle(): int
    {
        $failed = 0;
        $rows = [];
        $check = function ($label, $ok, $hint = '') use (&$failed, &$rows) {
            $rows[] = [$ok ? 'OK' : 'FAIL', $label, $ok ? '—' : $hint];
            if (! $ok) {
                $failed++;
            }
        };
        $check('PHP >= 8.2', PHP_VERSION_ID >= 80200, 'Select matching PHP >=8.2 for web and CLI');
        foreach (['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'fileinfo', 'ctype', 'tokenizer', 'xml', 'dom', 'gd', 'session', 'filter'] as $ext) {
            $check('PHP extension: '.$ext, extension_loaded($ext), 'Enable extension for CLI and web');
        }
        $check('GD WebP', function_exists('imagewebp'), 'GD must include WebP support');
        $key = (string) config('app.key', '');
        $decoded = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : $key;
        $check('APP_KEY valid', is_string($decoded) && Encrypter::supported($decoded, config('app.cipher')), 'Generate a key ONLY on initial install; restore original key on upgrade');
        foreach ([storage_path(), storage_path('framework/views'), storage_path('framework/sessions'), storage_path('framework/cache'), base_path('bootstrap/cache')] as $dir) {
            $check('Writable: '.str_replace(base_path().'/', '', $dir), is_dir($dir) && is_writable($dir), 'Set ownership/permissions, not chmod 777');
        }
        try {
            DB::connection()->getPdo();
            $check('Database connection', true);
            foreach (['users', 'roles', 'sessions', 'cache', 'material_orders', 'otp_challenges', 'demo_payment_attempts'] as $table) {
                $check('Table: '.$table, Schema::hasTable($table), 'Run migrations against the intended database');
            }
            $check('r0.4 schema', Schema::hasColumn('material_orders', 'reservation_expires_at'), 'php artisan migrate --force');
            $check('Roles seeded', Schema::hasTable('roles') && DB::table('roles')->count() >= 4, 'php artisan db:seed --force');
            $check('Active administrator', Schema::hasTable('users') && DB::table('users')->join('roles', 'roles.id', '=', 'users.role_id')->where('roles.name', 'admin')->where('users.status', 'active')->exists(), 'php artisan tisheh:admin');
        } catch (\Throwable $e) {
            $check('Database/schema', false, 'Check private DB credentials, privileges, and migrate; no secret values printed');
        }
        $doc = $this->option('public-path') ?: public_path();
        $real = realpath($doc);
        $base = realpath(base_path());
        $check('Document root exists', $real !== false, 'Create/configure the domain document root');
        if ($real) {
            $check('Private app is outside document root', ! ($base === $real || str_starts_with($base, $real.'/')), 'Only public assets/index.php may be web accessible');
            foreach (['.env', 'composer.json', 'artisan', 'vendor', 'tisheh-source.zip'] as $unsafe) {
                $check('Not public: '.$unsafe, ! file_exists($real.'/'.$unsafe), 'Move private source and archives outside document root');
            }$check('Public launcher', is_file($real.'/index.php'), 'Set document root to app/public or use tisheh:cpanel-publish');
        }
        if ($this->option('production')) {
            $check('APP_ENV=production', app()->environment('production'), 'Use production on the real public site');
            $check('APP_DEBUG=false', config('app.debug') === false, 'Disable debug');
            $check('HTTPS APP_URL', str_starts_with(config('app.url'), 'https://'), 'Configure valid HTTPS domain');
            $check('Secure encrypted sessions', config('session.secure') === true && config('session.encrypt') === true, 'SESSION_SECURE_COOKIE=true and SESSION_ENCRYPT=true');
            $check('Demo disabled', config('integrations.demo_enabled') === false, 'TISHEH_DEMO_ENABLED=false');
            $check('Demo OTP disabled', config('integrations.otp_demo_enabled') === false, 'TISHEH_DEMO_OTP_ENABLED=false');
            $check('Proxy allowlist is not wildcard', ! in_array('*', config('tisheh.trusted_proxies') ?? [], true), 'Trust only actual proxy IPs');
            $check('Frame policy not wildcard', config('tisheh.frame_ancestors') !== '*', 'Use self or explicit trusted origins');
        }
        $this->table(['Result', 'Check', 'Action if failed'], $rows);
        $this->line('Manual checks still required: web PHP matches CLI; HTTPS; /storage image loading; cron; backup/restore; domain access controls.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
