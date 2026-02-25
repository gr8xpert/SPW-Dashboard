<?php

namespace App\Http\Controllers\Webmaster;

use App\Http\Controllers\Controller;
use App\Models\CreditTransaction;
use Illuminate\Http\Request;

class TimesheetController extends Controller
{
    /**
     * View my logged hours per client, per ticket.
     */
    public function index(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $transactions = CreditTransaction::where('user_id', auth()->id())
            ->where('type', 'deduction')
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->with(['client', 'ticket'])
            ->orderByDesc('created_at')
            ->get();

        $totalHours = abs($transactions->sum('hours'));

        // Group by client
        $byClient = $transactions->groupBy('client_id')->map(function ($items) {
            return [
                'client_name' => $items->first()->client->company_name ?? 'Unknown',
                'total_hours' => abs($items->sum('hours')),
                'ticket_count' => $items->pluck('ticket_id')->unique()->count(),
            ];
        });

        return view('webmaster.timesheet.index', compact(
            'transactions', 'totalHours', 'byClient', 'from', 'to'
        ));
    }
}
