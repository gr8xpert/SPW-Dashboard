<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Services\CampaignSendService;
use Illuminate\Console\Command;

class ProcessScheduledCampaigns extends Command
{
    protected $signature   = 'campaigns:process-scheduled';
    protected $description = 'Dispatch campaigns whose scheduled_at time has passed';

    public function handle(CampaignSendService $service): int
    {
        $campaigns = Campaign::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($campaigns->isEmpty()) {
            return self::SUCCESS;
        }

        $this->info("Found {$campaigns->count()} campaign(s) to dispatch.");

        foreach ($campaigns as $campaign) {
            try {
                $service->dispatch($campaign);
                $this->info("  ✓ Dispatched #{$campaign->id}: {$campaign->name}");
            } catch (\Throwable $e) {
                $this->error("  ✗ Failed #{$campaign->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
