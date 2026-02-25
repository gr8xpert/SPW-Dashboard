<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE templates MODIFY COLUMN mode ENUM('unlayer', 'html', 'plain') NOT NULL DEFAULT 'html'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE templates MODIFY COLUMN mode ENUM('wysiwyg', 'html', 'ai', 'plaintext') NOT NULL DEFAULT 'wysiwyg'");
    }
};
