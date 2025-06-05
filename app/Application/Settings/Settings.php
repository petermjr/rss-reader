<?php

declare(strict_types=1);

namespace App\Application\Settings;

class Settings implements SettingsInterface
{
    private array $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function get(string $key = '', $default = null)
    {
        if (empty($key)) {
            return $this->settings;
        }

        if (isset($this->settings[$key])) {
            return $this->settings[$key];
        }

        return $default;
    }
} 