<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->unsignedBigInteger('ticket_id')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['purchase', 'deduction', 'refund', 'adjustment', 'bonus']);
            $table->decimal('hours', 8, 2);  // positive for credit, negative for deduction
            $table->decimal('rate', 8, 2)->nullable();  // price per hour (for purchases)
            $table->string('description', 500);
            $table->decimal('balance_after', 8, 2);  // running balance after transaction
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('ticket_id')->references('id')->on('support_tickets')->onDelete('set null');
            $table->index(['client_id', 'created_at']);
            $table->index('ticket_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
    }
};
