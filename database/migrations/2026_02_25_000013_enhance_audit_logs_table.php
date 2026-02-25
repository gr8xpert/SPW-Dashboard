<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Make client_id nullable (some audit events are system-wide)
            $table->unsignedBigInteger('client_id')->nullable()->change();

            // Add resource tracking columns (renamed from entity for clarity)
            $table->string('resource_type', 50)->nullable()->after('entity_id');
            $table->unsignedBigInteger('resource_id')->nullable()->after('resource_type');

            // Track old/new values for change tracking
            $table->json('old_values')->nullable()->after('details');
            $table->json('new_values')->nullable()->after('old_values');

            // Impersonation tracking
            $table->unsignedBigInteger('impersonated_by')->nullable()->after('user_agent');

            $table->index(['user_id', 'action']);
            $table->index(['resource_type', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'action']);
            $table->dropIndex(['resource_type', 'resource_id']);
            $table->dropColumn(['resource_type', 'resource_id', 'old_values', 'new_values', 'impersonated_by']);
        });
    }
};
