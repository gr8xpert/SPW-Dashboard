<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public function index()
    {
        $client   = Auth::user()->client;
        $settings = Setting::where('client_id', $client->id)->pluck('setting_value', 'setting_key');

        return view('client.settings.index', compact('client', 'settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:200',
            'timezone'     => 'required|string',
        ]);

        Auth::user()->client->update($data);

        return back()->with('success', 'Settings saved.');
    }

    public function regenerateApiKey(Request $request)
    {
        Auth::user()->client->update(['api_key' => Str::random(64)]);

        return back()->with('success', 'API key regenerated.');
    }
}
