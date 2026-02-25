<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('image_library', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('filename', 255);
            $table->string('original_filename', 255);
            $table->string('file_path', 500);
            $table->unsignedInteger('file_size')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('mime_type', 50);
            $table->string('alt_text', 500)->nullable();
            $table->string('folder', 100)->default('general');
            $table->timestamp('created_at')->useCurrent();

            $table->index('client_id');
            $table->index(['client_id', 'folder']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_library');
    }
};
