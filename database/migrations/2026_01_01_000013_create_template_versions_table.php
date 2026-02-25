<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->unsignedInteger('version_number');
            $table->longText('html_content')->nullable();
            $table->json('json_design')->nullable();
            $table->text('plain_text_content')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('template_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_versions');
    }
};
