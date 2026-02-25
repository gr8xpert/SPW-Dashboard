<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->enum('type', ['static', 'dynamic'])->default('static');
            $table->json('filter_rules')->nullable();
            $table->unsignedInteger('contacts_count')->default(0);
            $table->timestamps();

            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_lists');
    }
};
