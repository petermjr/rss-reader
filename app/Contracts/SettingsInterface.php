<?php

declare(strict_types=1);

namespace App\Contracts;

interface SettingsInterface
{
    /**
     * Get a setting value
     *
     * @param string $key The setting key
     * @param mixed $default The default value if setting not found
     * @return mixed The setting value
     */
    public function get(string $key, $default = null);
} 