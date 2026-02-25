<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'                  => 'Starter',
                'slug'                  => 'starter',
                'max_contacts'          => 500,
                'max_emails_per_month'  => 1000,
                'max_templates'         => 10,
                'max_users'             => 2,
                'max_image_storage_mb'  => 100,
                'max_languages'         => 1,
                'ai_search_enabled'     => false,
                'widget_included'       => true,
                'mailer_included'       => true,
                'price_monthly'         => 29.00,
                'price_yearly'          => 290.00,
                'sort_order'            => 1,
                'features'              => json_encode([
                    'automations'            => false,
                    'ab_testing'             => false,
                    'ai_generation_monthly'  => 5,
                    'html_editor'            => false,
                    'brand_kit'              => false,
                    'conditional_content'    => false,
                    'custom_domain'          => false,
                    'white_label'            => false,
                    'dark_mode_preview'      => false,
                    'template_lock'          => false,
                    'version_history_limit'  => 3,
                    'saved_blocks_limit'     => 5,
                    'stock_photos'           => false,
                    'pre_send_checks'        => 'basic',
                    'map_view'               => true,
                    'wishlist'               => true,
                    'inquiry_form'           => true,
                ]),
            ],
            [
                'name'                  => 'Professional',
                'slug'                  => 'professional',
                'max_contacts'          => 5000,
                'max_emails_per_month'  => 10000,
                'max_templates'         => 50,
                'max_users'             => 5,
                'max_image_storage_mb'  => 1024,
                'max_languages'         => 5,
                'ai_search_enabled'     => true,
                'widget_included'       => true,
                'mailer_included'       => true,
                'price_monthly'         => 79.00,
                'price_yearly'          => 790.00,
                'sort_order'            => 2,
                'features'              => json_encode([
                    'automations'            => true,
                    'ab_testing'             => true,
                    'ai_generation_monthly'  => 50,
                    'html_editor'            => true,
                    'brand_kit'              => true,
                    'conditional_content'    => true,
                    'custom_domain'          => false,
                    'white_label'            => false,
                    'dark_mode_preview'      => true,
                    'template_lock'          => true,
                    'version_history_limit'  => 50,
                    'saved_blocks_limit'     => 100,
                    'stock_photos'           => true,
                    'pre_send_checks'        => 'full',
                    'map_view'               => true,
                    'wishlist'               => true,
                    'inquiry_form'           => true,
                    'property_share'         => true,
                ]),
            ],
            [
                'name'                  => 'Enterprise',
                'slug'                  => 'enterprise',
                'max_contacts'          => 999999,
                'max_emails_per_month'  => 999999,
                'max_templates'         => 999999,
                'max_users'             => 999999,
                'max_image_storage_mb'  => 10240,
                'max_languages'         => 999,
                'ai_search_enabled'     => true,
                'widget_included'       => true,
                'mailer_included'       => true,
                'price_monthly'         => 199.00,
                'price_yearly'          => 1990.00,
                'sort_order'            => 3,
                'features'              => json_encode([
                    'automations'            => true,
                    'ab_testing'             => true,
                    'ai_generation_monthly'  => 999999,
                    'html_editor'            => true,
                    'brand_kit'              => true,
                    'conditional_content'    => true,
                    'custom_domain'          => true,
                    'white_label'            => true,
                    'dark_mode_preview'      => true,
                    'template_lock'          => true,
                    'version_history_limit'  => 999999,
                    'saved_blocks_limit'     => 999999,
                    'stock_photos'           => true,
                    'pre_send_checks'        => 'full_custom',
                    'map_view'               => true,
                    'wishlist'               => true,
                    'inquiry_form'           => true,
                    'property_share'         => true,
                    'advanced_analytics'     => true,
                ]),
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('plans')->updateOrInsert(['slug' => $plan['slug']], $plan);
        }
    }
}
