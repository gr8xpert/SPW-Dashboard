<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    /**
     * Show onboarding wizard.
     */
    public function index()
    {
        $client = auth()->user()->client;
        $client->load('plan');

        // Determine current step based on what's filled in
        $step = 1;
        if ($client->api_key && $client->api_url) $step = 3;
        if ($client->default_language) $step = 4;
        if ($client->domain) $step = 5;

        return view('client.onboarding.index', compact('client', 'step'));
    }

    /**
     * Save onboarding step data.
     */
    public function saveStep(Request $request)
    {
        $client = auth()->user()->client;

        switch ($request->input('step')) {
            case 2: // CRM credentials
                $request->validate([
                    'api_url' => 'required|url|max:500',
                ]);
                $client->update($request->only(['api_url']));
                break;

            case 3: // Language
                $request->validate([
                    'default_language' => 'required|string|max:10',
                ]);
                $client->update($request->only(['default_language']));
                break;

            case 4: // Platform
                // Just advance, no data to save
                break;

            case 5: // Install widget
                $request->validate([
                    'domain' => 'required|string|max:255',
                ]);
                $client->update(['domain' => $request->domain]);
                break;

            case 6: // Verify connection
                // Test API call from widget
                break;
        }

        return back()->with('success', 'Step saved.');
    }

    /**
     * Complete onboarding.
     */
    public function complete()
    {
        return redirect()->route('dashboard.home')
            ->with('success', 'Welcome! Your widget is ready to go.');
    }
}
