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
        $thirtyDaysAgo = now()->subDays(30);

        // Summary cards
        $totalIssued = CreditTransaction::where('type', 'purchase')->sum('hours');
        $totalBalance = Client::sum('credit_balance');
        $used30d = abs(CreditTransaction::where('type', 'deduction')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->sum('hours'));
        $clientCount = Client::where('credit_balance', '>', 0)->count();

        $summary = [
            'total_issued'    => $totalIssued,
            'in_circulation'  => $totalBalance,
            'used_30d'        => $used30d,
            'avg_per_client'  => $clientCount > 0 ? $totalBalance / $clientCount : 0,
        ];

        // Per-client credit balances
        $clientCredits = Client::with('plan')
            ->select('id', 'domain', 'plan_id', 'credit_balance')
            ->where('credit_balance', '>', 0)
            ->orWhereHas('creditTransactions', function ($q) use ($thirtyDaysAgo) {
                $q->where('created_at', '>=', $thirtyDaysAgo);
            })
            ->get()
            ->each(function ($credit) use ($thirtyDaysAgo) {
                $credit->client_id = $credit->id;
                $credit->plan = $credit->plan?->name ?? 'N/A';
                $credit->balance = $credit->credit_balance;
                $credit->used_30d = abs(CreditTransaction::where('client_id', $credit->id)
                    ->where('type', 'deduction')
                    ->where('created_at', '>=', $thirtyDaysAgo)
                    ->sum('hours'));
            });

        // Per-webmaster summary
        $webmasterCredits = User::where('role', 'webmaster')
            ->withCount('assignedTickets as clients_count')
            ->get()
            ->map(function ($wm) use ($thirtyDaysAgo) {
                $wm->webmaster_id = $wm->id;
                $wm->balance = 0;
                $wm->used_30d = abs(CreditTransaction::where('user_id', $wm->id)
                    ->where('type', 'deduction')
                    ->where('created_at', '>=', $thirtyDaysAgo)
                    ->sum('hours'));
                $wm->allocated = CreditTransaction::where('user_id', $wm->id)
                    ->where('type', 'purchase')
                    ->sum('hours');
                return $wm;
            });

        // Recent transactions
        $recentTransactions = CreditTransaction::with(['client', 'user'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(function ($txn) {
                $txn->entity_name = $txn->client?->domain ?? $txn->user?->name ?? '—';
                $txn->amount = $txn->hours;
                $txn->balance_after = $txn->balance_after ?? 0;
                return $txn;
            });

        return view('admin.credits.overview', compact(
            'summary', 'clientCredits', 'webmasterCredits', 'recentTransactions',
            'dateFrom', 'dateTo'
        ));
    }
}
