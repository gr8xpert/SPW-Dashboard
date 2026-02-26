<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\PaddleBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillingController extends Controller
{
    public function index()
    {
        $client = Auth::user()->client;
        $plans  = Plan::where('is_active', true)->orderBy('sort_order')->get();

        $paddle = PaddleBillingService::for('widget');

        return view('client.billing.index', [
            'client'          => $client,
            'plans'           => $plans,
            'paddleVendorId'  => $paddle->getVendorId(),
            'paddleSandbox'   => $paddle->isSandbox(),
        ]);
    }

    public function subscribe(Request $request)
    {
        return response()->json([
            'error' => 'Please use the Subscribe button to complete your purchase through Paddle checkout.',
        ], 400);
    }

    public function cancel(Request $request)
    {
        Auth::user()->client->update(['status' => 'cancelled']);

        return back()->with('success', 'Subscription cancelled.');
    }
}
