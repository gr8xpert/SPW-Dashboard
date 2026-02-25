<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installer_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_token', 64)->unique();
            $table->foreignId('license_key_id')->constrained('license_keys')->onDelete('cascade');
            $table->string('domain', 255);
            $table->string('platform', 50)->nullable();  // wordpress, custom, etc.
            $table->json('languages')->nullable();
            $table->json('page_slugs')->nullable();
            $table->enum('status', ['started', 'completed', 'failed'])->default('started');
            $table->json('generated_files')->nullable();
            $table->timestamps();

            $table->index('license_key_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installer_sessions');
    }
};
