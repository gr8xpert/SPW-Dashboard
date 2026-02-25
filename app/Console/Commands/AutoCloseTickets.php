<?php

namespace App\Console\Commands;

use App\Models\SupportTicket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoCloseTickets extends Command
{
    protected $signature = 'tickets:auto-close';
    protected $description = 'Auto-close resolved tickets after the reopen window expires';

    public function handle(): int
    {
        $days = config('smartmailer.tickets.auto_close_days', 7);

        $tickets = SupportTicket::where('status', 'resolved')
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '<', now()->subDays($days))
            ->get();

        foreach ($tickets as $ticket) {
            $ticket->close();
            $this->info("Closed ticket #{$ticket->id}: {$ticket->subject}");
        }

        $this->info("Auto-closed {$tickets->count()} tickets.");

        Log::info('Auto-close tickets completed', ['count' => $tickets->count()]);

        return Command::SUCCESS;
    }
}
