<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillingController extends Controller
{
    public function index()
    {
        $client = Auth::user()->client;
        $plans  = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return view('client.billing.index', compact('client', 'plans'));
    }

    public function subscribe(Request $request)
    {
        $request->validate(['plan_id' => 'required|exists:plans,id']);

        $plan = Plan::findOrFail($request->plan_id);

        // Stripe integration placeholder
        Auth::user()->client->update([
            'plan_id' => $plan->id,
            'status'  => 'active',
        ]);

        return back()->with('success', 'Subscribed to ' . $plan->name . ' plan!');
    }

    public function cancel(Request $request)
    {
        Auth::user()->client->update(['status' => 'cancelled']);

        return back()->with('success', 'Subscription cancelled.');
    }
}
