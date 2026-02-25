<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('source', 100)->nullable()->after('consent_source');
            $table->string('unsubscribe_token', 64)->nullable()->after('double_opt_in_token');

            $table->index(['client_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex(['client_id', 'source']);
            $table->dropColumn(['source', 'unsubscribe_token']);
        });
    }
};
