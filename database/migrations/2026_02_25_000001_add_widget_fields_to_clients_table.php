<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Widget CRM connection
            $table->string('domain', 255)->nullable()->after('custom_domain');
            $table->text('api_url')->nullable()->after('api_secret');       // CRM API endpoint
            $table->json('widget_features')->nullable()->after('api_url');  // {ai_search, max_languages, map_view, etc.}
            $table->boolean('ai_search_enabled')->default(false)->after('widget_features');
            $table->string('openrouter_api_key', 255)->nullable()->after('ai_search_enabled');
            $table->string('default_language', 10)->default('en')->after('openrouter_api_key');
            $table->string('owner_email', 255)->nullable()->after('default_language');  // for inquiry emails

            // Widget access control
            $table->boolean('widget_enabled')->default(true)->after('owner_email');
            $table->boolean('admin_override')->default(false)->after('widget_enabled');
            $table->boolean('is_internal')->default(false)->after('admin_override');
            $table->enum('billing_source', ['paddle', 'manual', 'internal'])->default('paddle')->after('is_internal');

            // Paddle billing (replaces Stripe)
            $table->string('paddle_subscription_id', 255)->nullable()->after('stripe_subscription_id');
            $table->string('paddle_customer_id', 255)->nullable()->after('paddle_subscription_id');
            $table->enum('subscription_status', ['active', 'grace', 'expired', 'manual', 'internal'])->default('active')->after('paddle_customer_id');
            $table->timestamp('grace_ends_at')->nullable()->after('subscription_status');
            $table->timestamp('subscription_expires_at')->nullable()->after('grace_ends_at');

            // Credit hours for support
            $table->decimal('credit_balance', 8, 2)->default(0)->after('subscription_expires_at');
            $table->decimal('credit_rate', 8, 2)->default(50.00)->after('credit_balance');

            // Indexes
            $table->index('domain');
            $table->index('subscription_status');
            $table->index('billing_source');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['domain']);
            $table->dropIndex(['subscription_status']);
            $table->dropIndex(['billing_source']);

            $table->dropColumn([
                'domain', 'api_url', 'widget_features', 'ai_search_enabled',
                'openrouter_api_key', 'default_language', 'owner_email',
                'widget_enabled', 'admin_override', 'is_internal', 'billing_source',
                'paddle_subscription_id', 'paddle_customer_id',
                'subscription_status', 'grace_ends_at', 'subscription_expires_at',
                'credit_balance', 'credit_rate',
            ]);
        });
    }
};
