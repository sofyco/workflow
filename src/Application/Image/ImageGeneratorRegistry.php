<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Image;

use RuntimeException;
use Sofyco\Workflow\Application\Support\SettingsAccessor;

final readonly class ImageGeneratorRegistry
{
    /**
     * @param iterable<ImageGeneratorInterface> $generators
     */
    public function __construct(
        private iterable $generators,
    ) {
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function generate(string $prompt, array $settings): ImageGenerationResult
    {
        $provider = SettingsAccessor::string($settings, 'provider');

        foreach ($this->generators as $generator) {
            if ($generator->supports($provider)) {
                return $generator->generate($prompt, $settings);
            }
        }

        throw new RuntimeException(sprintf('Image provider "%s" not found.', $provider));
    }
}
