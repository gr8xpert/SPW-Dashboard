<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('cascade');
            $table->enum('ab_variant', ['A', 'B'])->nullable();
            $table->longText('personalized_html')->nullable();
            $table->enum('status', ['pending', 'queued', 'sent', 'delivered', 'opened', 'clicked', 'bounced', 'failed', 'skipped'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->enum('bounce_type', ['hard', 'soft'])->nullable();
            $table->string('bounce_reason', 500)->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->string('smtp_message_id', 200)->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'status']);
            $table->index(['client_id', 'campaign_id']);
            $table->index(['status', 'next_retry_at']);
            $table->index('smtp_message_id');
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_sends');
    }
};
