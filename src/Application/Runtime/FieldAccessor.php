<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Runtime;

final readonly class FieldAccessor
{
    /**
     * @param array<string, mixed> $context
     */
    public function get(array $context, string $field): mixed
    {
        $segments = explode('.', $field);
        $value = $context;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
