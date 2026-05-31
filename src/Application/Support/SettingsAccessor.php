<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Support;

final class SettingsAccessor
{
    /**
     * @param array<string, mixed> $settings
     */
    public static function string(array $settings, string $key, string $default = ''): string
    {
        $value = $settings[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    /**
     * @param array<string, mixed> $settings
     */
    public static function float(array $settings, string $key, float $default = 0.0): float
    {
        $value = $settings[$key] ?? $default;

        return is_float($value) || is_int($value) ? (float) $value : $default;
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>|null
     */
    public static function arrayOrNull(array $settings, string $key): ?array
    {
        $value = $settings[$key] ?? null;

        if (!is_array($value)) {
            return null;
        }

        /** @var array<string, mixed> $value */
        return $value;
    }
}
