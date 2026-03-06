<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table 1: Custom location groups
        if (!Schema::hasTable('client_custom_location_groups')) {
            Schema::create('client_custom_location_groups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('slug');
                $table->foreignId('parent_group_id')->nullable()->constrained('client_custom_location_groups')->cascadeOnDelete();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();

                $table->unique(['client_id', 'slug'], 'clg_client_slug_unique');
                $table->index(['client_id', 'parent_group_id', 'sort_order'], 'clg_client_parent_sort_idx');
            });
        }

        // Table 2: Location mappings (feed locations -> custom groups)
        if (!Schema::hasTable('client_location_mappings')) {
            Schema::create('client_location_mappings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->cascadeOnDelete();
                $table->foreignId('custom_group_id')->constrained('client_custom_location_groups')->cascadeOnDelete();
                $table->string('feed_location_id', 50);
                $table->string('feed_location_name')->nullable();
                $table->string('feed_location_type', 20)->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['client_id', 'feed_location_id'], 'clm_client_feed_loc_unique');
                $table->index(['custom_group_id', 'sort_order'], 'clm_group_sort_idx');
            });
        }

        // Add column to clients table
        if (!Schema::hasColumn('clients', 'custom_location_grouping_enabled')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->boolean('custom_location_grouping_enabled')->default(false)->after('enabled_languages');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('clients', 'custom_location_grouping_enabled')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('custom_location_grouping_enabled');
            });
        }

        Schema::dropIfExists('client_location_mappings');
        Schema::dropIfExists('client_custom_location_groups');
    }
};
