<?php

namespace App\Http\Controllers\Webmaster;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CannedResponse;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Services\CreditService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(
        protected CreditService $creditService
    ) {}

    /**
     * My assigned tickets.
     */
    public function index(Request $request)
    {
        $query = SupportTicket::assignedTo(auth()->id())
            ->with(['client', 'creator']);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $tickets = $query->orderByDesc('updated_at')->paginate(20);

        return view('webmaster.tickets.index', compact('tickets'));
    }

    /**
     * View and work on a ticket.
     */
    public function show(SupportTicket $ticket)
    {
        if ($ticket->assigned_to !== auth()->id()) {
            abort(403, 'This ticket is not assigned to you.');
        }

        $ticket->load(['client', 'creator', 'messages.user', 'creditTransactions']);
        $cannedResponses = CannedResponse::orderBy('category')->get();

        return view('webmaster.tickets.show', compact('ticket', 'cannedResponses'));
    }

    /**
     * Start work on a ticket.
     */
    public function startWork(SupportTicket $ticket)
    {
        if ($ticket->assigned_to !== auth()->id()) {
            abort(403);
        }

        $ticket->startWork();

        AuditLog::log('ticket.work_started', 'support_ticket', $ticket->id);

        return back()->with('success', 'Work started on this ticket.');
    }

    /**
     * Add a message to the ticket.
     */
    public function addMessage(Request $request, SupportTicket $ticket)
    {
        if ($ticket->assigned_to !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'message'     => 'required|string|max:10000',
            'is_internal' => 'boolean',
        ]);

        TicketMessage::create([
            'ticket_id'   => $ticket->id,
            'user_id'     => auth()->id(),
            'message'     => $request->message,
            'is_internal' => $request->boolean('is_internal', false),
        ]);

        return back()->with('success', 'Message posted.');
    }

    /**
     * Book credit hours for work done.
     */
    public function bookHours(Request $request, SupportTicket $ticket)
    {
        if ($ticket->assigned_to !== auth()->id()) {
            abort(403);
        }

        if (!$ticket->canBookHours()) {
            return back()->with('error', 'Cannot book hours on this ticket.');
        }

        $request->validate([
            'hours'       => 'required|numeric|min:0.25|max:100',
            'description' => 'required|string|max:500',
        ]);

        try {
            $this->creditService->bookHours(
                $ticket->client_id,
                $ticket->id,
                auth()->id(),
                $request->hours,
                $request->description
            );

            return back()->with('success', "{$request->hours} hours booked.");
        } catch (\App\Exceptions\InsufficientCreditsException $e) {
            return back()->with('error', 'Client has insufficient credit balance. Admin has been notified.');
        }
    }

    /**
     * Submit ticket for admin review.
     */
    public function submitForReview(SupportTicket $ticket)
    {
        if ($ticket->assigned_to !== auth()->id()) {
            abort(403);
        }

        $ticket->submitForReview();

        AuditLog::log('ticket.submitted_for_review', 'support_ticket', $ticket->id);

        // TODO: Notify admin

        return back()->with('success', 'Ticket submitted for review.');
    }
}
