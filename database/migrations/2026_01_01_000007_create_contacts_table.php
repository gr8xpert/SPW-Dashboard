<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('email', 320);
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('company', 200)->nullable();
            $table->json('tags')->nullable();
            $table->json('custom_fields')->nullable();
            $table->integer('lead_score')->default(0);
            $table->enum('engagement_tier', ['hot', 'active', 'lukewarm', 'cold', 'dead'])->default('active');
            $table->enum('status', ['subscribed', 'unsubscribed', 'bounced', 'complained', 'pending'])->default('subscribed');
            $table->timestamp('consent_date')->nullable();
            $table->string('consent_source', 200)->nullable();
            $table->string('timezone', 50)->nullable();
            $table->timestamp('last_opened_at')->nullable();
            $table->timestamp('last_clicked_at')->nullable();
            $table->timestamp('last_emailed_at')->nullable();
            $table->unsignedInteger('total_opens')->default(0);
            $table->unsignedInteger('total_clicks')->default(0);
            $table->unsignedInteger('total_emails_received')->default(0);
            $table->string('double_opt_in_token', 64)->nullable();
            $table->timestamp('double_opt_in_confirmed_at')->nullable();
            $table->timestamps();

            $table->unique(['email', 'client_id']);
            $table->index(['client_id', 'status']);
            $table->index(['client_id', 'engagement_tier']);
            $table->index(['client_id', 'lead_score']);
            $table->index(['client_id', 'last_opened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
