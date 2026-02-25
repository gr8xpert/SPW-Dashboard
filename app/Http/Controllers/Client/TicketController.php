<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $client = auth()->user()->client;

        $query = SupportTicket::forClient($client->id)
            ->with(['assignee', 'creator']);

        if ($request->filled('status')) {
            if ($request->status === 'open') {
                $query->open();
            } elseif ($request->status === 'closed') {
                $query->closed();
            }
        }

        $tickets = $query->orderByDesc('updated_at')->paginate(20);

        return view('client.tickets.index', compact('tickets'));
    }

    public function create()
    {
        return view('client.tickets.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject'     => 'required|string|max:255',
            'description' => 'required|string|max:10000',
            'priority'    => 'required|in:low,medium,high,urgent',
            'category'    => 'required|in:bug,feature,maintenance,content,design,other',
        ]);

        $client = auth()->user()->client;

        $ticket = SupportTicket::create([
            'client_id'   => $client->id,
            'created_by'  => auth()->id(),
            'subject'     => $request->subject,
            'description' => $request->description,
            'priority'    => $request->priority,
            'category'    => $request->category,
        ]);

        AuditLog::log('ticket.created', 'support_ticket', $ticket->id);

        // TODO: Send notification to super admin

        return redirect()->route('dashboard.tickets.show', $ticket)
            ->with('success', 'Ticket created successfully.');
    }

    public function show(SupportTicket $ticket)
    {
        $client = auth()->user()->client;

        // Ensure ticket belongs to this client
        if ($ticket->client_id !== $client->id) {
            abort(403);
        }

        $ticket->load(['creator', 'assignee', 'creditTransactions']);

        // Only show public messages to clients
        $messages = $ticket->messages()
            ->public()
            ->with('user')
            ->orderBy('created_at')
            ->get();

        return view('client.tickets.show', compact('ticket', 'messages'));
    }

    public function addMessage(Request $request, SupportTicket $ticket)
    {
        $client = auth()->user()->client;
        if ($ticket->client_id !== $client->id) {
            abort(403);
        }

        if ($ticket->isClosed() && !$ticket->canReopen()) {
            return back()->with('error', 'This ticket is closed and cannot receive new messages.');
        }

        $request->validate(['message' => 'required|string|max:10000']);

        TicketMessage::create([
            'ticket_id'   => $ticket->id,
            'user_id'     => auth()->id(),
            'message'     => $request->message,
            'is_internal' => false,  // client messages are always public
        ]);

        // Reopen if resolved and client adds a message
        if ($ticket->status === 'resolved' && $ticket->canReopen()) {
            $ticket->reopen();
        }

        return back()->with('success', 'Message sent.');
    }
}
