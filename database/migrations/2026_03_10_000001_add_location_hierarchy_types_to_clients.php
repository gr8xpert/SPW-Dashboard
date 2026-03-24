<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds location hierarchy type configuration to clients table.
     * Allows dashboard admins to configure which location types to use
     * as parent/child in cascading location dropdowns.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Location hierarchy types for cascading dropdowns
            // Parent type: first dropdown (e.g., area, municipality, province)
            // Child type: second dropdown (e.g., city, municipality, town)
            $table->string('location_parent_type', 50)->default('municipality')->after('custom_feature_grouping_enabled');
            $table->string('location_child_type', 50)->default('city')->after('location_parent_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['location_parent_type', 'location_child_type']);
        });
    }
};
