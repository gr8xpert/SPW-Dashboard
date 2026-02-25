<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smtp_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('name', 100);
            $table->enum('provider', ['ses', 'sendgrid', 'mailgun', 'postmark', 'smtp', 'platform'])->default('smtp');
            $table->string('host', 200);
            $table->unsignedSmallInteger('port')->default(587);
            $table->string('username', 200);
            $table->text('password_encrypted');
            $table->enum('encryption', ['tls', 'ssl', 'none'])->default('tls');
            $table->string('from_email', 320)->nullable();
            $table->string('from_name', 200)->nullable();
            $table->unsignedInteger('daily_limit')->default(500);
            $table->unsignedInteger('hourly_limit')->default(100);
            $table->unsignedInteger('sent_today')->default(0);
            $table->unsignedInteger('sent_this_hour')->default(0);
            $table->timestamp('last_hour_reset_at')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_default')->default(false);
            $table->decimal('reputation_score', 5, 2)->default(100.00);
            $table->timestamps();

            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smtp_accounts');
    }
};
