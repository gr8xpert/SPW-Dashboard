<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_suppressions', function (Blueprint $table) {
            $table->id();
            $table->string('email', 320)->unique();
            $table->enum('reason', ['hard_bounce', 'complaint', 'spam_trap', 'manual']);
            $table->unsignedBigInteger('source_client_id')->nullable();
            $table->timestamp('added_at')->useCurrent();

            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_suppressions');
    }
};
