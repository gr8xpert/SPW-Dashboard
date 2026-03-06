<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates tables for custom property type and feature grouping.
     */
    public function up(): void
    {
        // Custom Property Type Groups
        Schema::create('client_custom_property_type_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->unsignedBigInteger('parent_group_id')->nullable();
            $table->string('parent_feed_type_id', 50)->nullable(); // Feed property type to nest under
            $table->string('parent_feed_type_name')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'parent_group_id']);
            $table->index(['client_id', 'slug']);
            $table->foreign('parent_group_id')
                  ->references('id')
                  ->on('client_custom_property_type_groups')
                  ->onDelete('set null');
        });

        // Property Type Mappings (feed types -> custom groups)
        Schema::create('client_property_type_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('custom_group_id');
            $table->string('feed_type_id', 50);
            $table->string('feed_type_name')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['client_id', 'feed_type_id']);
            $table->foreign('custom_group_id')
                  ->references('id')
                  ->on('client_custom_property_type_groups')
                  ->onDelete('cascade');
        });

        // Custom Feature Groups
        Schema::create('client_custom_feature_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->unsignedBigInteger('parent_group_id')->nullable();
            $table->string('parent_feed_feature_id', 50)->nullable(); // Feed feature to nest under
            $table->string('parent_feed_feature_name')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'parent_group_id']);
            $table->index(['client_id', 'slug']);
            $table->foreign('parent_group_id')
                  ->references('id')
                  ->on('client_custom_feature_groups')
                  ->onDelete('set null');
        });

        // Feature Mappings (feed features -> custom groups)
        Schema::create('client_feature_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('custom_group_id');
            $table->string('feed_feature_id', 50);
            $table->string('feed_feature_name')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['client_id', 'feed_feature_id']);
            $table->foreign('custom_group_id')
                  ->references('id')
                  ->on('client_custom_feature_groups')
                  ->onDelete('cascade');
        });

        // Add grouping enabled flags to clients table
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('custom_property_type_grouping_enabled')->default(false)->after('custom_location_grouping_enabled');
            $table->boolean('custom_feature_grouping_enabled')->default(false)->after('custom_property_type_grouping_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['custom_property_type_grouping_enabled', 'custom_feature_grouping_enabled']);
        });

        Schema::dropIfExists('client_feature_mappings');
        Schema::dropIfExists('client_custom_feature_groups');
        Schema::dropIfExists('client_property_type_mappings');
        Schema::dropIfExists('client_custom_property_type_groups');
    }
};
