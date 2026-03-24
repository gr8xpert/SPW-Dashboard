<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE widget_analytics MODIFY COLUMN event_type ENUM('search', 'property_view', 'card_click', 'wishlist_add', 'inquiry', 'share', 'pdf_download')");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE widget_analytics MODIFY COLUMN event_type ENUM('search', 'property_view', 'card_click', 'wishlist_add', 'inquiry', 'share')");
    }
};
