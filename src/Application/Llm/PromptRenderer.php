<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Llm;

final readonly class PromptRenderer
{
    /**
     * @param array<string, mixed> $context
     */
    public function render(string $template, array $context): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/',
            function (array $matches) use ($context): string {
                $value = $this->resolvePath($context, $matches[1]);

                if (is_scalar($value) || $value === null) {
                    return $value === null ? '' : (string) $value;
                }

                return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            },
            $template,
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function resolvePath(array $context, string $path): mixed
    {
        $segments = explode('.', $path);
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
