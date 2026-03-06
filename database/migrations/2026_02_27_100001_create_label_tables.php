<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Default labels managed by Super Admin
        if (!Schema::hasTable('default_labels')) {
            Schema::create('default_labels', function (Blueprint $table) {
                $table->id();
                $table->string('language', 10);           // en_US, es_ES, de_DE, etc.
                $table->string('label_key', 100);         // e.g., "listing_type", "bedrooms"
                $table->text('label_value');              // The translated text
                $table->timestamps();

                $table->unique(['language', 'label_key']);
                $table->index('language');
            });
        }

        // Client-specific label overrides
        if (!Schema::hasTable('client_label_overrides')) {
            Schema::create('client_label_overrides', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->string('language', 10);
                $table->string('label_key', 100);
                $table->text('label_value');
                $table->timestamps();

                $table->unique(['client_id', 'language', 'label_key']);
                $table->index(['client_id', 'language']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_label_overrides');
        Schema::dropIfExists('default_labels');
    }
};
