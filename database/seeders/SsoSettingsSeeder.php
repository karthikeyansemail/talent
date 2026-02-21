<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SsoSettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['google', 'microsoft', 'okta'] as $provider) {
            DB::table('sso_settings')->insertOrIgnore([
                'provider'     => $provider,
                'is_enabled'   => false,
                'client_id'    => null,
                'client_secret'=> null,
                'extra_config' => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }
}
