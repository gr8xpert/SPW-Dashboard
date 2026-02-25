<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Get the enterprise plan (or starter if enterprise doesn't exist)
        $planId = DB::table('plans')->where('slug', 'enterprise')->value('id')
            ?? DB::table('plans')->where('slug', 'starter')->value('id');

        // Create the platform owner client (client_id = 1)
        $clientId = DB::table('clients')->insertGetId([
            'company_name'        => 'Smart Property Widget',
            'subdomain'           => 'platform',
            'plan_id'             => $planId,
            'status'              => 'active',
            'api_key'             => Str::random(64),
            'api_secret'          => Hash::make(Str::random(32)),
            'timezone'            => 'UTC',
            'subscription_status' => 'internal',
            'billing_source'      => 'internal',
            'admin_override'      => true,
            'is_internal'         => true,
            'widget_enabled'      => true,
            'default_language'    => 'en',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Create super admin user
        DB::table('users')->insert([
            'client_id'         => $clientId,
            'name'              => 'Super Admin',
            'email'             => 'admin@smartpropertywidget.com',
            'password'          => Hash::make('Admin@123456'),
            'role'              => 'super_admin',
            'email_verified_at' => now(),
            'status'            => 'active',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }
}
