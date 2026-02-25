<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('max_languages')->default(1)->after('max_image_storage_mb');
            $table->boolean('ai_search_enabled')->default(false)->after('max_languages');
            $table->boolean('widget_included')->default(true)->after('ai_search_enabled');
            $table->boolean('mailer_included')->default(true)->after('widget_included');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['max_languages', 'ai_search_enabled', 'widget_included', 'mailer_included']);
        });
    }
};
