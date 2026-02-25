<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Services\WidgetSubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckWidgetSubscriptions extends Command
{
    protected $signature = 'widget:check-subscriptions';
    protected $description = 'Daily check: expire grace periods, start new grace periods, send reminders';

    public function __construct(
        protected WidgetSubscriptionService $subscriptionService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Checking widget subscriptions...');

        // 1. Expire clients whose grace period has ended
        $expiredGrace = Client::where('subscription_status', 'grace')
            ->where('grace_ends_at', '<', now())
            ->where('admin_override', false)
            ->where('is_internal', false)
            ->get();

        foreach ($expiredGrace as $client) {
            $this->subscriptionService->expireSubscription($client->id);
            $this->info("Expired: {$client->company_name} (ID: {$client->id})");
            // TODO: Send "Widget deactivated" email
        }

        // 2. Start grace for clients whose subscription has expired but aren't in grace yet
        $newlyExpired = Client::where('subscription_status', 'active')
            ->whereNotNull('subscription_expires_at')
            ->where('subscription_expires_at', '<', now())
            ->where('admin_override', false)
            ->where('is_internal', false)
            ->get();

        foreach ($newlyExpired as $client) {
            $this->subscriptionService->startGracePeriod($client->id);
            $this->info("Grace started: {$client->company_name} (ID: {$client->id})");
            // TODO: Send "Subscription expired, 7 days grace" email
        }

        // 3. Send reminder for clients at grace reminder day
        $reminderDay = config('smartmailer.widget.grace_reminder_day', 3);
        $graceClients = Client::where('subscription_status', 'grace')
            ->where('admin_override', false)
            ->where('is_internal', false)
            ->get();

        foreach ($graceClients as $client) {
            $daysInGrace = $client->grace_ends_at
                ? (int) now()->diffInDays($client->grace_ends_at, false)
                : 0;

            $gracePeriodDays = config('smartmailer.widget.grace_period_days', 7);
            $daysElapsed = $gracePeriodDays - $daysInGrace;

            if ($daysElapsed === $reminderDay) {
                $this->info("Reminder sent: {$client->company_name} ({$daysInGrace} days remaining)");
                // TODO: Send reminder email
            }
        }

        $this->info("Done. Expired: {$expiredGrace->count()}, Grace started: {$newlyExpired->count()}");

        Log::info('Widget subscription check completed', [
            'expired'       => $expiredGrace->count(),
            'grace_started' => $newlyExpired->count(),
        ]);

        return Command::SUCCESS;
    }
}
