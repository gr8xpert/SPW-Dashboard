<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 200);
            $table->string('subdomain', 100)->unique()->nullable();
            $table->string('custom_domain', 200)->unique()->nullable();
            $table->foreignId('plan_id')->constrained('plans');
            $table->enum('status', ['trial', 'active', 'suspended', 'cancelled'])->default('trial');
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('stripe_customer_id', 100)->nullable();
            $table->string('stripe_subscription_id', 100)->nullable();
            $table->string('api_key', 64)->unique()->nullable();
            $table->string('api_secret', 64)->nullable();
            $table->string('timezone', 50)->default('UTC');
            $table->timestamps();

            $table->index('status');
            $table->index('subdomain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
