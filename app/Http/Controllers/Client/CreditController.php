<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\CreditService;
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

        return view('client.credits.buy', compact('client', 'packs'));
    }
}
