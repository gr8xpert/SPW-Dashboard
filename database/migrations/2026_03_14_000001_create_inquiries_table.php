<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->onDelete('set null');
            $table->string('name', 200);
            $table->string('email', 320);
            $table->string('phone', 50)->nullable();
            $table->text('message')->nullable();
            $table->string('property_ref', 100)->nullable();
            $table->string('property_title', 500)->nullable();
            $table->string('property_url', 1000)->nullable();
            $table->string('property_price', 100)->nullable();
            $table->enum('status', ['new', 'contacted', 'converted', 'archived'])->default('new');
            $table->string('source', 50)->default('widget');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['client_id', 'created_at']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
