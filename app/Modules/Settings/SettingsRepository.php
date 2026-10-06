<?php

declare(strict_types=1);

namespace Adheri\Modules\Settings;

use Adheri\Database;

final class SettingsRepository
{
    /** @var list<string> */
    public const ADMIN_KEYS = [
        'org_name',
        'org_short_name',
        'contact_email_primary',
        'contact_email_info',
        'phone_primary',
        'phone_secondary',
        'address',
        'map_url',
        'social_facebook',
        'social_twitter',
        'social_instagram',
        'social_linkedin',
        'donation_min_amount',
        'donation_currency',
        'policy_cta_label',
        'home_mission',
        'home_mission_support',
        'about_vision',
        'seo_default_title',
        'seo_default_description',
    ];

    /** @return array<string, string|null> */
    public function all(): array
    {
        $rows = Database::fetchAll('SELECT setting_key, setting_value FROM site_settings');
        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row['setting_key']] = $row['setting_value'];
        }
        return $out;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $row = Database::fetch(
            'SELECT setting_value FROM site_settings WHERE setting_key = :key',
            ['key' => $key]
        );
        return $row['setting_value'] ?? $default;
    }

    public function set(string $key, ?string $value): void
    {
        Database::execute(
            'INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
            ['key' => $key, 'value' => $value]
        );
    }

    /** @param array<string, string|null> $pairs */
    public function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            $this->set($key, $value);
        }
    }
}
