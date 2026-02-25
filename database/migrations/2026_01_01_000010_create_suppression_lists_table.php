<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppression_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('email', 320);
            $table->enum('reason', ['unsubscribed', 'hard_bounce', 'complaint', 'manual']);
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->timestamp('added_at')->useCurrent();

            $table->unique(['email', 'client_id']);
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppression_lists');
    }
};
