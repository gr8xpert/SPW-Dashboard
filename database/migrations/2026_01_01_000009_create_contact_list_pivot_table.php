<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_list_pivot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('cascade');
            $table->foreignId('list_id')->constrained('contact_lists')->onDelete('cascade');
            $table->timestamp('added_at')->useCurrent();

            $table->unique(['contact_id', 'list_id']);
            $table->index('client_id');
            $table->index('list_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_list_pivot');
    }
};
