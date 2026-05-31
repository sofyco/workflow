<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Video;

final readonly class VideoRenderResult
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
