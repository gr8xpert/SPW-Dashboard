<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade')->unique();
            $table->unsignedTinyInteger('priority')->default(5);
            $table->enum('status', ['waiting', 'processing', 'completed', 'failed'])->default('waiting');
            $table->unsignedInteger('batch_size')->default(50);
            $table->unsignedInteger('current_offset')->default(0);
            $table->unsignedInteger('total_to_send')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_batch_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['status', 'priority', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_queue');
    }
};
