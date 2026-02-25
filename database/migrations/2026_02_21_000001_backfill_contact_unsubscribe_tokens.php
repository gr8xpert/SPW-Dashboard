<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('contacts')
            ->whereNull('double_opt_in_token')
            ->orderBy('id')
            ->each(function ($contact) {
                DB::table('contacts')
                    ->where('id', $contact->id)
                    ->update(['double_opt_in_token' => Str::random(64)]);
            });
    }

    public function down(): void
    {
        // Not reversible — tokens are not harmful to keep
    }
};
