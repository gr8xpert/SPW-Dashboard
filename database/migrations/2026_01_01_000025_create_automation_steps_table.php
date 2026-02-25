<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('automations')->onDelete('cascade');
            $table->unsignedTinyInteger('step_order');
            $table->enum('step_type', ['email', 'delay', 'condition', 'tag', 'remove_tag']);
            $table->json('config');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['automation_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_steps');
    }
};
