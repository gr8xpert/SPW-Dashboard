<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ResetSmtpHourlyLimits extends Command
{
    protected $signature   = 'smtp:reset-hourly';
    protected $description = 'Reset SMTP hourly send counters (placeholder — counters tracked via timestamps)';

    public function handle(): int
    {
        // Hourly rate limiting is enforced by counting sent_at timestamps
        // within the current hour window, so no reset is needed.
        $this->info('SMTP hourly counters use rolling windows — no reset needed.');

        return self::SUCCESS;
    }
}
