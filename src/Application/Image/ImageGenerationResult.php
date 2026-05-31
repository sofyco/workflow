<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Image;

final readonly class ImageGenerationResult
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $localPath,
        public string $mimeType,
        public string $extension,
        public array $metadata = [],
    ) {
    }
}
