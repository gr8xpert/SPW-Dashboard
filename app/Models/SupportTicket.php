<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use BelongsToTenant;

    protected $table = 'support_tickets';

    protected $fillable = [
        'client_id', 'created_by', 'assigned_to', 'subject', 'description',
        'priority', 'status', 'category', 'total_hours_spent', 'resolved_at',
    ];

    protected $casts = [
        'total_hours_spent' => 'decimal:2',
        'resolved_at'       => 'datetime',
    ];

    // --- Relationships ---

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id');
    }

    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class, 'ticket_id');
    }

    // --- Scopes ---

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'assigned', 'in_progress', 'review']);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', ['resolved', 'closed']);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    // --- Status checks ---

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'assigned', 'in_progress', 'review']);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['resolved', 'closed']);
    }

    public function canReopen(): bool
    {
        return $this->status === 'resolved'
            && $this->resolved_at
            && $this->resolved_at->diffInDays(now()) <= config('smartmailer.tickets.reopen_window_days', 7);
    }

    public function canBookHours(): bool
    {
        return in_array($this->status, ['assigned', 'in_progress'])
            && $this->assigned_to !== null;
    }

    // --- Actions ---

    public function assign(int $userId): void
    {
        $this->update([
            'assigned_to' => $userId,
            'status'      => 'assigned',
        ]);
    }

    public function startWork(): void
    {
        $this->update(['status' => 'in_progress']);
    }

    public function submitForReview(): void
    {
        $this->update(['status' => 'review']);
    }

    public function resolve(): void
    {
        $this->update([
            'status'      => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }

    public function reopen(): void
    {
        $this->update([
            'status'      => 'open',
            'resolved_at' => null,
        ]);
    }

    public function addHours(float $hours): void
    {
        $this->increment('total_hours_spent', $hours);
    }

    // --- SLA ---

    public function getSlaHours(): int
    {
        return config("smartmailer.tickets.sla.{$this->priority}", 24);
    }

    public function isSlaBreach(): bool
    {
        if ($this->isClosed()) return false;

        $firstResponse = $this->messages()
            ->where('user_id', '!=', $this->created_by)
            ->oldest()
            ->first();

        if ($firstResponse) return false;

        return $this->created_at->diffInHours(now()) > $this->getSlaHours();
    }

    public function getSlaTimeRemaining(): ?int
    {
        if ($this->isClosed()) return null;

        $firstResponse = $this->messages()
            ->where('user_id', '!=', $this->created_by)
            ->oldest()
            ->first();

        if ($firstResponse) return null;

        return max(0, $this->getSlaHours() - $this->created_at->diffInHours(now()));
    }
}
