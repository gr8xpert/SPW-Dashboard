<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\CreditService;
use App\Services\PaddleBillingService;
use Illuminate\Http\Request;

class CreditController extends Controller
{
    public function __construct(
        protected CreditService $creditService
    ) {}

    /**
     * Credit balance and transaction history.
     */
    public function index()
    {
        $client = auth()->user()->client;
        $transactions = $this->creditService->getTransactions($client->id, 50);
        $isLowBalance = $this->creditService->isLowBalance($client->id);

        return view('client.credits.index', compact('client', 'transactions', 'isLowBalance'));
    }

    /**
     * Buy credit packs via Paddle.
     */
    public function buy()
    {
        $client = auth()->user()->client;
        $packs = config('smartmailer.credits.packs');

        $paddle = PaddleBillingService::for('platform');

        return view('client.credits.buy', [
            'client'          => $client,
            'packs'           => $packs,
            'balance'         => $client->credit_balance,
            'hourlyRate'      => $client->credit_rate ?: config('smartmailer.credits.default_rate'),
            'paddleVendorId'  => $paddle->getVendorId(),
            'paddleSandbox'   => $paddle->isSandbox(),
        ]);
    }

    /**
     * Purchase credit pack (Paddle.js handles checkout; webhook fulfils).
     */
    public function purchase(Request $request)
    {
        return redirect()->route('dashboard.credits.buy')
            ->with('info', 'Please use the Buy button to complete your purchase through our payment provider.');
    }
}
