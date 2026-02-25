<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('name', 200);
            $table->string('identifier', 100);
            $table->text('html_content');
            $table->timestamps();

            $table->unique(['identifier', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_blocks');
    }
};
