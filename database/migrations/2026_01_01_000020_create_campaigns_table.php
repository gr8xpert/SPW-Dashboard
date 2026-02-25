<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('name', 200);
            $table->string('subject', 500);
            $table->string('subject_b', 500)->nullable();
            $table->string('preview_text', 200)->nullable();
            $table->string('from_name', 200);
            $table->string('from_email', 320);
            $table->string('reply_to', 320)->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedBigInteger('list_id')->nullable();
            $table->json('segment_rules')->nullable();
            $table->longText('html_content');
            $table->text('plain_text_content')->nullable();
            $table->unsignedBigInteger('smtp_account_id')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'queued', 'sending', 'paused', 'sent', 'cancelled', 'failed'])->default('draft');
            $table->boolean('ab_test_enabled')->default(false);
            $table->unsignedTinyInteger('ab_test_percentage')->default(10);
            $table->enum('ab_winner_criteria', ['open_rate', 'click_rate'])->default('open_rate');
            $table->unsignedTinyInteger('ab_test_duration_hours')->default(4);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('total_sent')->default(0);
            $table->unsignedInteger('total_delivered')->default(0);
            $table->unsignedInteger('total_opened')->default(0);
            $table->unsignedInteger('total_clicked')->default(0);
            $table->unsignedInteger('total_bounced')->default(0);
            $table->unsignedInteger('total_unsubscribed')->default(0);
            $table->unsignedInteger('total_complained')->default(0);
            $table->boolean('pre_rendered')->default(false);
            $table->decimal('spam_score', 3, 1)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
