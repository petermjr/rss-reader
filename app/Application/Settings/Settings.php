<?php

declare(strict_types=1);

namespace App\Application\Settings;

use App\Contracts\SettingsInterface;

class Settings implements SettingsInterface
{
    /**
     * @var array
     */
    private array $settings;

    /**
     * Settings constructor.
     *
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Get a setting value
     *
     * @param string $key The setting key
     * @param mixed $default The default value if setting not found
     * @return mixed The setting value
     */
    public function get(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }
} 