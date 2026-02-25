<?php

namespace App\Services;

use App\Exceptions\InsufficientCreditsException;
use App\Models\Client;
use App\Models\CreditTransaction;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\DB;

class CreditService
{
    /**
     * Book credit hours against a client for a ticket.
     * Wrapped in DB transaction with row lock to prevent race conditions.
     *
     * @throws InsufficientCreditsException
     */
    public function bookHours(
        int $clientId,
        int $ticketId,
        int $userId,
        float $hours,
        string $description
    ): CreditTransaction {
        return DB::transaction(function () use ($clientId, $ticketId, $userId, $hours, $description) {
            $client = Client::lockForUpdate()->findOrFail($clientId);

            if ($client->credit_balance < $hours) {
                throw new InsufficientCreditsException(
                    "Insufficient credits: {$client->credit_balance}h available, {$hours}h required"
                );
            }

            $client->decrement('credit_balance', $hours);

            $ticket = SupportTicket::find($ticketId);
            if ($ticket) {
                $ticket->addHours($hours);
            }

            return CreditTransaction::create([
                'client_id'     => $clientId,
                'ticket_id'     => $ticketId,
                'user_id'       => $userId,
                'type'          => 'deduction',
                'hours'         => -$hours,  // negative for deduction
                'rate'          => $client->credit_rate,
                'description'   => $description,
                'balance_after' => $client->fresh()->credit_balance,
            ]);
        });
    }

    /**
     * Add credit hours to a client (purchase or bonus).
     */
    public function addCredits(
        int $clientId,
        int $userId,
        float $hours,
        string $type = 'purchase',
        string $description = '',
        ?float $rate = null,
        ?int $ticketId = null
    ): CreditTransaction {
        return DB::transaction(function () use ($clientId, $userId, $hours, $type, $description, $rate, $ticketId) {
            $client = Client::lockForUpdate()->findOrFail($clientId);
            $client->increment('credit_balance', $hours);

            return CreditTransaction::create([
                'client_id'     => $clientId,
                'ticket_id'     => $ticketId,
                'user_id'       => $userId,
                'type'          => $type,
                'hours'         => $hours,
                'rate'          => $rate,
                'description'   => $description ?: "Added {$hours} credit hours",
                'balance_after' => $client->fresh()->credit_balance,
            ]);
        });
    }

    /**
     * Refund credit hours for a ticket.
     */
    public function refund(
        int $clientId,
        int $userId,
        float $hours,
        string $description,
        ?int $ticketId = null
    ): CreditTransaction {
        return $this->addCredits($clientId, $userId, $hours, 'refund', $description, null, $ticketId);
    }

    /**
     * Get balance for a client.
     */
    public function getBalance(int $clientId): float
    {
        return (float) Client::find($clientId)?->credit_balance ?? 0;
    }

    /**
     * Get transaction history for a client.
     */
    public function getTransactions(int $clientId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return CreditTransaction::where('client_id', $clientId)
            ->with(['user', 'ticket'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Check if client balance is below warning threshold.
     */
    public function isLowBalance(int $clientId): bool
    {
        $client = Client::find($clientId);
        if (!$client) return false;

        $threshold = config('smartmailer.credits.low_balance_hours', 2);
        return $client->credit_balance < $threshold && $client->credit_balance > 0;
    }

    /**
     * Check if client has zero balance.
     */
    public function isZeroBalance(int $clientId): bool
    {
        return ($this->getBalance($clientId)) <= 0;
    }
}
