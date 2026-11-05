<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InitializeKey extends Command
{
    protected $signature = 'tisheh:key-init';

    protected $description = 'Initialize a missing application key, never rotate an existing encryption key';

    public function handle(): int
    {
        if (! is_file(base_path('.env'))) {
            $this->error('Create the private .env from the correct template first.');

            return self::FAILURE;
        }
        if ((string) config('app.key') !== '') {
            $this->info('Existing application key preserved. Encrypted identity data must retain its original key.');

            return self::SUCCESS;
        }
        if (app()->configurationIsCached()) {
            $this->error('Clear stale configuration before initializing a fresh key.');

            return self::FAILURE;
        }

        return $this->call('key:generate', ['--force' => true]);
    }
}
