<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TicketNotificationService
{
    /**
     * Generate a unique reply-to address for a ticket.
     */
    public function getReplyToAddress(SupportTicket $ticket): string
    {
        $hash = hash_hmac('sha256', $ticket->id, config('app.key'));
        $shortHash = substr($hash, 0, 12);

        return "ticket-{$ticket->id}-{$shortHash}@smartpropertywidget.com";
    }

    /**
     * Verify a reply-to address hash.
     */
    public function verifyReplyToHash(int $ticketId, string $hash): bool
    {
        $expected = hash_hmac('sha256', $ticketId, config('app.key'));
        return hash_equals(substr($expected, 0, 12), $hash);
    }

    /**
     * Parse ticket ID from a reply-to address.
     */
    public function parseReplyToAddress(string $email): ?array
    {
        if (preg_match('/^ticket-(\d+)-([a-f0-9]{12})@/', $email, $matches)) {
            return [
                'ticket_id' => (int) $matches[1],
                'hash'      => $matches[2],
            ];
        }
        return null;
    }

    /**
     * Notify super admin about a new ticket.
     */
    public function notifyTicketCreated(SupportTicket $ticket): void
    {
        $admins = User::where('role', 'super_admin')->get();

        foreach ($admins as $admin) {
            // TODO: Send email notification
            Log::info("Ticket #{$ticket->id} created — notify admin {$admin->email}");
        }
    }

    /**
     * Notify webmaster about ticket assignment.
     */
    public function notifyTicketAssigned(SupportTicket $ticket): void
    {
        if (!$ticket->assigned_to) return;

        $webmaster = User::find($ticket->assigned_to);
        if (!$webmaster) return;

        // TODO: Send email notification
        Log::info("Ticket #{$ticket->id} assigned to {$webmaster->email}");
    }

    /**
     * Notify client about ticket resolution.
     */
    public function notifyTicketResolved(SupportTicket $ticket): void
    {
        $creator = $ticket->creator;
        if (!$creator) return;

        // TODO: Send email notification
        Log::info("Ticket #{$ticket->id} resolved — notify client {$creator->email}");
    }

    /**
     * Send low credit warning to client.
     */
    public function notifyLowCredits(int $clientId, float $balance): void
    {
        // TODO: Send email notification
        Log::warning("Low credit balance for client {$clientId}: {$balance}h remaining");
    }
}
