<?php

declare(strict_types=1);

namespace App\Environment;

class EnvironmentHelper
{
    public function getArray(string $key): array
    {
        $result = [];

        foreach (getenv() as $envKey => $value) {
            if (strpos($envKey, $key) !== 0) {
                continue;
            }

            $envKey = substr($envKey, strlen($key) + 1);
            $components = explode('_', $envKey, 2);
            if (count($components) !== 2) {
                continue;
            }

            if (!is_numeric($components[0])) {
                continue;
            }

            if (!isset($result[(int) $components[0]])) {
                $result[(int) $components[0]] = [];
            }

            $result[(int) $components[0]][$components[1]] = $value;
        }

        return $result;
    }
}

