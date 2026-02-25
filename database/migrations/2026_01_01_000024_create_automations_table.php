<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('name', 200);
            $table->enum('trigger_type', ['contact_added', 'tag_added', 'contact_updated', 'date_field', 'manual', 'engagement_drop']);
            $table->json('trigger_config')->nullable();
            $table->enum('status', ['active', 'paused', 'draft'])->default('draft');
            $table->unsignedInteger('total_enrolled')->default(0);
            $table->unsignedInteger('total_completed')->default(0);
            $table->timestamps();

            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automations');
    }
};
