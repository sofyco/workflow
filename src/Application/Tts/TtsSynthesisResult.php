<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Tts;

final readonly class TtsSynthesisResult
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
