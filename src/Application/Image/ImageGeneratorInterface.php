<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Image;

interface ImageGeneratorInterface
{
    public function supports(string $provider): bool;

    /**
     * @param array<string, mixed> $settings
     */
    public function generate(string $prompt, array $settings): ImageGenerationResult;
}
