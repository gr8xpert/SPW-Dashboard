<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('widget_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->enum('event_type', [
                'search', 'property_view', 'card_click',
                'wishlist_add', 'inquiry', 'share',
            ]);
            $table->json('event_data')->nullable();
            $table->string('session_id', 64)->nullable();
            $table->string('url', 500)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['client_id', 'event_type']);
            $table->index(['client_id', 'created_at']);
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widget_analytics');
    }
};
