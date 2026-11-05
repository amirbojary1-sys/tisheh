<?php

namespace App\Console\Commands;

use App\Models\MaterialOrder;
use App\Services\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ExpireReservations extends Command
{
    protected $signature = 'tisheh:expire-reservations';

    protected $description = 'Release expired awaiting-seller reservations once; no financial operations';

    public function handle(OrderService $s): int
    {
        $n = 0;
        MaterialOrder::where('status', 'awaiting_seller')->where('reservation_expires_at', '<=', now())->select('id')->chunkById(100, function ($rows) use (&$n, $s) {
            foreach ($rows as $row) {
                $n += (int) $s->expire($row->id);
            }
        });
        Cache::put('tisheh.reservations_last_run', now()->toIso8601String(), now()->addDays(2));
        $this->info("Expired reservations: $n");

        return self::SUCCESS;
    }
}
