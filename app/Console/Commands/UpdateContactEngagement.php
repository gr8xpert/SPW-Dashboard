<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateContactEngagement extends Command
{
    protected $signature   = 'contacts:update-engagement';
    protected $description = 'Update engagement tier and lead score for all contacts';

    public function handle(): int
    {
        // Hot: opened or clicked in last 30 days
        DB::table('contacts')
            ->where('last_opened_at', '>=', now()->subDays(30))
            ->update(['engagement_tier' => 'hot', 'lead_score' => DB::raw('LEAST(lead_score + 5, 100)')]);

        // Active: opened in last 90 days
        DB::table('contacts')
            ->where('last_opened_at', '>=', now()->subDays(90))
            ->where('last_opened_at', '<', now()->subDays(30))
            ->update(['engagement_tier' => 'active']);

        // Lukewarm: opened in last 180 days
        DB::table('contacts')
            ->where('last_opened_at', '>=', now()->subDays(180))
            ->where('last_opened_at', '<', now()->subDays(90))
            ->update(['engagement_tier' => 'lukewarm']);

        // Cold: opened more than 180 days ago
        DB::table('contacts')
            ->where('last_opened_at', '<', now()->subDays(180))
            ->whereNotNull('last_opened_at')
            ->update(['engagement_tier' => 'cold']);

        // Dead: never opened
        DB::table('contacts')
            ->whereNull('last_opened_at')
            ->update(['engagement_tier' => 'dead']);

        $this->info('Contact engagement tiers updated.');

        return self::SUCCESS;
    }
}
