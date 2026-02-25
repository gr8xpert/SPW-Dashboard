<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TicketEmailReplyController extends Controller
{
    /**
     * POST /api/internal/tickets/{ticket}/reply-from-email
     *
     * Called by n8n when a user replies to a ticket notification email.
     */
    public function handle(Request $request, SupportTicket $ticket): JsonResponse
    {
        $request->validate([
            'from_email' => 'required|email',
            'body'       => 'required|string',
            'raw_body'   => 'nullable|string',
            'attachments' => 'nullable|array',
        ]);

        // Find the user by email who is associated with this ticket
        $user = User::where('email', $request->from_email)
            ->where(function ($q) use ($ticket) {
                $q->where('client_id', $ticket->client_id)
                    ->orWhere('role', 'super_admin')
                    ->orWhere('role', 'webmaster');
            })
            ->first();

        if (!$user) {
            Log::warning("Email reply from unknown user: {$request->from_email} for ticket #{$ticket->id}");
            return response()->json(['error' => 'User not found on this ticket'], 404);
        }

        // Determine if internal (admin/webmaster) or public (client)
        $isInternal = in_array($user->role, ['super_admin', 'webmaster']);

        // Clean up the message body (strip quoted text)
        $cleanBody = $this->stripQuotedText($request->body);

        // Create the message
        $message = TicketMessage::create([
            'ticket_id'   => $ticket->id,
            'user_id'     => $user->id,
            'message'     => $cleanBody,
            'is_internal' => $isInternal,
            'attachments' => $request->attachments,
        ]);

        // If client replies on a resolved ticket, reopen it
        if ($ticket->status === 'resolved' && $ticket->canReopen() && !$isInternal) {
            $ticket->reopen();
        }

        // TODO: Send notification to the other party

        return response()->json([
            'success'    => true,
            'message_id' => $message->id,
        ]);
    }

    /**
     * Strip quoted reply text from email body.
     */
    protected function stripQuotedText(string $body): string
    {
        // Remove lines starting with '>'
        $lines = explode("\n", $body);
        $cleaned = [];
        $hitQuote = false;

        foreach ($lines as $line) {
            if (preg_match('/^>/', $line) || preg_match('/^On .+ wrote:/', $line)) {
                $hitQuote = true;
                continue;
            }
            if (preg_match('/^---\s*Original Message/i', $line)) {
                break;
            }
            if (preg_match('/^_{5,}/', $line)) {
                break;  // Outlook separator
            }
            if (!$hitQuote) {
                $cleaned[] = $line;
            }
        }

        return trim(implode("\n", $cleaned));
    }
}
