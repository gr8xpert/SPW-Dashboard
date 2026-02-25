<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Http\Request;

class CreditController extends Controller
{
    public function __construct(
        protected CreditService $creditService
    ) {}

    /**
     * Add/adjust credits for a specific client.
     */
    public function addCredits(Request $request, Client $client)
    {
        $request->validate([
            'hours'       => 'required|numeric|min:0.25|max:1000',
            'type'        => 'required|in:purchase,adjustment,bonus',
            'description' => 'required|string|max:500',
            'rate'        => 'nullable|numeric|min:0',
        ]);

        $this->creditService->addCredits(
            $client->id,
            auth()->id(),
            $request->hours,
            $request->type,
            $request->description,
            $request->rate
        );

        AuditLog::log('credits.added', 'client', $client->id, [
            'hours' => $request->hours,
            'type'  => $request->type,
        ]);

        return back()->with('success', "{$request->hours} credit hours added.");
    }

    /**
     * Overview dashboard: per-client and per-webmaster analytics.
     */
    public function overview(Request $request)
    {
        $dateFrom = $request->input('from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('to', now()->toDateString());

        // Per-client summary
        $clientSummary = Client::select('clients.id', 'clients.company_name', 'clients.credit_balance')
            ->withSum(['creditTransactions as total_purchased' => function ($q) use ($dateFrom, $dateTo) {
                $q->where('type', 'purchase')
                    ->whereBetween('created_at', [$dateFrom, $dateTo]);
            }], 'hours')
            ->withSum(['creditTransactions as total_used' => function ($q) use ($dateFrom, $dateTo) {
                $q->where('type', 'deduction')
                    ->whereBetween('created_at', [$dateFrom, $dateTo]);
            }], 'hours')
            ->having('total_purchased', '>', 0)
            ->orHaving('total_used', '<', 0)
            ->orWhere('credit_balance', '>', 0)
            ->get();

        // Per-webmaster summary
        $webmasterSummary = User::where('role', 'webmaster')
            ->withCount('assignedTickets')
            ->get()
            ->map(function ($wm) use ($dateFrom, $dateTo) {
                $wm->total_hours = CreditTransaction::where('user_id', $wm->id)
                    ->where('type', 'deduction')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->sum('hours');
                return $wm;
            });

        // Summary cards
        $totalRevenue = CreditTransaction::where('type', 'purchase')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('SUM(hours * COALESCE(rate, 0)) as revenue')
            ->value('revenue') ?? 0;

        $totalSold = CreditTransaction::where('type', 'purchase')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->sum('hours');

        $totalConsumed = abs(CreditTransaction::where('type', 'deduction')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->sum('hours'));

        $totalBalance = Client::sum('credit_balance');

        return view('admin.credits.overview', compact(
            'clientSummary', 'webmasterSummary',
            'totalRevenue', 'totalSold', 'totalConsumed', 'totalBalance',
            'dateFrom', 'dateTo'
        ));
    }
}
