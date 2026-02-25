<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportTicket::with(['client', 'creator', 'assignee']);

        if ($request->filled('status')) {
            if ($request->status === 'open') {
                $query->open();
            } elseif ($request->status === 'closed') {
                $query->closed();
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $tickets = $query->orderByDesc('updated_at')->paginate(25);
        $webmasters = User::where('role', 'webmaster')->get();

        return view('admin.tickets.index', compact('tickets', 'webmasters'));
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load(['client', 'creator', 'assignee', 'messages.user', 'creditTransactions.user']);
        $webmasters = User::where('role', 'webmaster')->get();

        return view('admin.tickets.show', compact('ticket', 'webmasters'));
    }

    public function assign(Request $request, SupportTicket $ticket)
    {
        $request->validate(['assigned_to' => 'required|exists:users,id']);

        $ticket->assign($request->assigned_to);

        AuditLog::log('ticket.assigned', 'support_ticket', $ticket->id, [
            'assigned_to' => $request->assigned_to,
        ]);

        // TODO: Send notification to webmaster

        return back()->with('success', 'Ticket assigned successfully.');
    }

    public function addMessage(Request $request, SupportTicket $ticket)
    {
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

        return back()->with('success', 'Message added.');
    }

    public function resolve(SupportTicket $ticket)
    {
        $ticket->resolve();

        AuditLog::log('ticket.resolved', 'support_ticket', $ticket->id);

        // TODO: Send notification to client

        return back()->with('success', 'Ticket resolved.');
    }

    public function sendBack(SupportTicket $ticket)
    {
        $ticket->update(['status' => 'in_progress']);

        return back()->with('success', 'Ticket sent back to webmaster for more work.');
    }
}
