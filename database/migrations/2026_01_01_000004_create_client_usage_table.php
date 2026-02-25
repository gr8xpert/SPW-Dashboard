<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->char('month', 7);
            $table->unsignedInteger('emails_sent')->default(0);
            $table->unsignedInteger('contacts_count')->default(0);
            $table->unsignedInteger('api_calls')->default(0);
            $table->unsignedInteger('ai_generations')->default(0);
            $table->decimal('image_storage_used_mb', 10, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['client_id', 'month']);
            $table->index('month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_usage');
    }
};
