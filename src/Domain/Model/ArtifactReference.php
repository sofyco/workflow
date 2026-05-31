<?php declare(strict_types=1);

namespace Sofyco\Workflow\Domain\Model;

use Sofyco\Workflow\Domain\Enum\ArtifactType;

final readonly class ArtifactReference
{
    public function __construct(
        public string       $artifactId,
        public string       $alias,
        public ArtifactType $type,
        public string       $mimeType,
    )
    {
    }
}
