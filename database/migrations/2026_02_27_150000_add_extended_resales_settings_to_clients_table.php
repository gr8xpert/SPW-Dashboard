<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('clients', 'resales_settings')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->json('resales_settings')->nullable()->after('resales_agency_code');
            });
        }

        if (!Schema::hasColumn('clients', 'enabled_languages')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->json('enabled_languages')->nullable()->after('resales_settings');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('clients', 'enabled_languages')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('enabled_languages');
            });
        }

        if (Schema::hasColumn('clients', 'resales_settings')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('resales_settings');
            });
        }
    }
};
