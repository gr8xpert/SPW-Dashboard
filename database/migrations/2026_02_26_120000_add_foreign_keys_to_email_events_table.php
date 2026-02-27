<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add foreign key constraints to email_events table.
     * Indexes already exist, so we only add foreign keys for referential integrity.
     */
    public function up(): void
    {
        // First, clean up any orphaned records that would violate foreign keys
        DB::statement('DELETE FROM email_events WHERE campaign_send_id NOT IN (SELECT id FROM campaign_sends)');
        DB::statement('DELETE FROM email_events WHERE campaign_id NOT IN (SELECT id FROM campaigns)');
        DB::statement('DELETE FROM email_events WHERE contact_id NOT IN (SELECT id FROM contacts)');

        Schema::table('email_events', function (Blueprint $table) {
            // Add foreign key constraints with cascade delete
            // Indexes already exist from original migration
            $table->foreign('campaign_send_id')
                ->references('id')
                ->on('campaign_sends')
                ->onDelete('cascade');

            $table->foreign('campaign_id')
                ->references('id')
                ->on('campaigns')
                ->onDelete('cascade');

            $table->foreign('contact_id')
                ->references('id')
                ->on('contacts')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_events', function (Blueprint $table) {
            $table->dropForeign(['campaign_send_id']);
            $table->dropForeign(['campaign_id']);
            $table->dropForeign(['contact_id']);
        });
    }
};
