<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_kits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade')->unique();
            $table->string('logo_url', 500)->nullable();
            $table->char('primary_color', 7)->default('#2563EB');
            $table->char('secondary_color', 7)->default('#1E40AF');
            $table->char('accent_color', 7)->default('#F59E0B');
            $table->string('font_heading', 100)->default('Arial');
            $table->string('font_body', 100)->default('Arial');
            $table->text('footer_html')->nullable();
            $table->json('social_links')->nullable();
            $table->text('company_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_kits');
    }
};
