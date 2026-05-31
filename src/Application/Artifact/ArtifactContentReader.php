<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Artifact;

use RuntimeException;
use Sofyco\Workflow\Domain\Model\Artifact;

final readonly class ArtifactContentReader
{
    public function __construct(
        private ArtifactStorageInterface $artifactStorage,
    )
    {
    }

    public function canReadAsText(Artifact $artifact): bool
    {
        return in_array($artifact->getMimeType(), [
            'text/plain',
            'text/markdown',
            'text/html',
            'text/csv',
            'application/json',
            'application/xml',
        ], true);
    }

    public function readText(Artifact $artifact): string
    {
        if (!$this->canReadAsText($artifact)) {
            throw new RuntimeException(sprintf(
                'Artifact "%s" with mime type "%s" is not readable as text.',
                $artifact->getId(),
                $artifact->getMimeType(),
            ));
        }

        return $this->artifactStorage->readContent($artifact);
    }

    /**
     * @return array<string, mixed>
     */
    public function readJson(Artifact $artifact): array
    {
        if ($artifact->getMimeType() !== 'application/json') {
            throw new RuntimeException('Artifact is not JSON.');
        }

        $decoded = json_decode($this->readText($artifact), true, flags: JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            throw new RuntimeException('JSON artifact must decode to an array.');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
