<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->unsignedBigInteger('folder_id')->nullable();
            $table->string('name', 200);
            $table->enum('category', ['newsletter', 'promotion', 'announcement', 'welcome', 'transactional', 'reengagement', 'event', 'custom'])->default('custom');
            $table->enum('mode', ['wysiwyg', 'html', 'ai', 'plaintext'])->default('wysiwyg');
            $table->longText('html_content')->nullable();
            $table->json('json_design')->nullable();
            $table->text('plain_text_content')->nullable();
            $table->string('thumbnail_path', 500)->nullable();
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_platform_template')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'category']);
            $table->index('is_platform_template');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
