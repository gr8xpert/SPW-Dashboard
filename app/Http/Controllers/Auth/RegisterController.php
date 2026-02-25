<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ContactList;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function show()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:200',
            'name'         => 'required|string|max:150',
            'email'        => 'required|email|max:320',
            'password'     => 'required|string|min:8|confirmed',
            'domain'       => 'nullable|string|max:255',
        ]);

        $starterPlan = Plan::where('slug', 'starter')->firstOrFail();

        $client = DB::transaction(function () use ($request, $starterPlan) {
            $subdomain = $this->generateSubdomain($request->company_name);

            $client = Client::create([
                'company_name'        => $request->company_name,
                'subdomain'           => $subdomain,
                'domain'              => $request->domain,
                'plan_id'             => $starterPlan->id,
                'status'              => 'trial',
                'trial_ends_at'       => now()->addDays(14),
                'api_key'             => Str::random(64),
                'api_secret'          => Hash::make(Str::random(32)),
                'timezone'            => 'UTC',
                'subscription_status' => 'active',
                'billing_source'      => 'paddle',
                'widget_enabled'      => true,
                'owner_email'         => $request->email,
                'default_language'    => 'en',
            ]);

            $user = User::create([
                'client_id' => $client->id,
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'role'      => 'admin',
                'status'    => 'active',
            ]);

            // Auto-create default contact lists for widget integration
            ContactList::create([
                'client_id'   => $client->id,
                'name'        => 'Widget Inquiries',
                'type'        => 'static',
                'description' => 'Contacts captured from widget property inquiry forms',
            ]);

            ContactList::create([
                'client_id'   => $client->id,
                'name'        => 'All Website Visitors',
                'type'        => 'static',
                'description' => 'Contacts from newsletter signups and website forms',
            ]);

            $user->sendEmailVerificationNotification();

            return $client;
        });

        $user = $client->users()->where('email', $request->email)->first();
        Auth::login($user);

        return redirect()->route('verification.notice');
    }

    protected function generateSubdomain(string $companyName): string
    {
        $base = Str::slug($companyName, '-');
        $subdomain = $base;
        $i = 1;

        while (Client::where('subdomain', $subdomain)->exists()) {
            $subdomain = $base . '-' . $i++;
        }

        return $subdomain;
    }
}
