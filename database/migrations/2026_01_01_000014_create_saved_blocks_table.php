<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('name', 200);
            $table->enum('category', ['header', 'footer', 'product_card', 'cta', 'social', 'divider', 'custom'])->default('custom');
            $table->text('html_content');
            $table->json('json_design')->nullable();
            $table->string('thumbnail_path', 500)->nullable();
            $table->timestamps();

            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_blocks');
    }
};
