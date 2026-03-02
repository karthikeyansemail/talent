<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\PlatformSetting;

class BrandingService
{
    public static function resolve(?Organization $org = null): array
    {
        // Check org-level white-label override first
        if ($org) {
            $settings = $org->settings ?? [];
            if (!empty($settings['custom_app_name'])) {
                return [
                    'app_name' => $settings['custom_app_name'],
                    'app_name_short' => $settings['custom_app_name'],
                    'app_name_accent' => '',
                    'logo_path' => $settings['custom_logo_path'] ?? null,
                ];
            }
        }

        // Fall back to global platform settings
        return [
            'app_name' => PlatformSetting::get('app_name', 'Nalam Pulse'),
            'app_name_short' => PlatformSetting::get('app_name_short', 'Nalam'),
            'app_name_accent' => PlatformSetting::get('app_name_accent', 'Pulse'),
            'logo_path' => PlatformSetting::get('app_logo_path'),
        ];
    }
}
