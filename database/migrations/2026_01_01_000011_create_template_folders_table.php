<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('name', 200);
            $table->unsignedBigInteger('parent_folder_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_folders');
    }
};
