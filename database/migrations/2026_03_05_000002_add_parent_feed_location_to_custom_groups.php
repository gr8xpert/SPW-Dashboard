<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('client_custom_location_groups', 'parent_feed_location_id')) {
            Schema::table('client_custom_location_groups', function (Blueprint $table) {
                $table->string('parent_feed_location_id', 50)->nullable()->after('parent_group_id');
                $table->string('parent_feed_location_name')->nullable()->after('parent_feed_location_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('client_custom_location_groups', function (Blueprint $table) {
            if (Schema::hasColumn('client_custom_location_groups', 'parent_feed_location_id')) {
                $table->dropColumn(['parent_feed_location_id', 'parent_feed_location_name']);
            }
        });
    }
};
