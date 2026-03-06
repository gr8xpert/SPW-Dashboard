<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Client-specific display preferences for locations, types, and features
        if (!Schema::hasTable('client_display_preferences')) {
            Schema::create('client_display_preferences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->enum('item_type', ['location', 'property_type', 'feature']); // What kind of item
                $table->string('item_id', 50);           // The ID of the location/type/feature
                $table->string('item_name')->nullable(); // Cached name for display
                $table->boolean('visible')->default(true); // Show or hide this item
                $table->integer('sort_order')->default(0); // Custom sort order (lower = first)
                $table->string('custom_name')->nullable(); // Optional custom display name
                $table->timestamps();

                $table->unique(['client_id', 'item_type', 'item_id']);
                $table->index(['client_id', 'item_type', 'visible']);
                $table->index(['client_id', 'item_type', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_display_preferences');
    }
};
