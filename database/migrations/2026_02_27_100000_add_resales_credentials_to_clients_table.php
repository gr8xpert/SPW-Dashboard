<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check each column individually before adding
        if (!Schema::hasColumn('clients', 'resales_client_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('resales_client_id')->nullable()->after('api_key');
            });
        }

        if (!Schema::hasColumn('clients', 'resales_api_key')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->text('resales_api_key')->nullable()->after('resales_client_id');
            });
        }

        if (!Schema::hasColumn('clients', 'resales_filter_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('resales_filter_id')->default('1')->after('resales_api_key');
            });
        }

        if (!Schema::hasColumn('clients', 'resales_agency_code')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('resales_agency_code', 10)->nullable()->after('resales_filter_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $columns = ['resales_client_id', 'resales_api_key', 'resales_filter_id', 'resales_agency_code'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('clients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
