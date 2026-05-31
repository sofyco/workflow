<?php declare(strict_types=1);

namespace Sofyco\Workflow\Infrastructure\Storage;

use LogicException;
use Sofyco\Workflow\Application\Artifact\ArtifactStorageInterface;
use Sofyco\Workflow\Domain\Enum\ArtifactType;
use Sofyco\Workflow\Domain\Model\Artifact;
use Sofyco\Workflow\Domain\Model\NodeExecution;
use Sofyco\Workflow\Domain\Model\WorkflowRun;

final readonly class S3ArtifactStorage implements ArtifactStorageInterface
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function storeFile(
        WorkflowRun $run,
        ?NodeExecution $execution,
        string $alias,
        string $localPath,
        ArtifactType $type,
        string $mimeType,
        string $extension,
        array $metadata = [],
    ): Artifact {
        throw new LogicException('Not implemented.');
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function storeContent(
        WorkflowRun $run,
        ?NodeExecution $execution,
        string $alias,
        string $content,
        ArtifactType $type,
        string $mimeType,
        string $extension,
        array $metadata = [],
    ): Artifact {
        throw new LogicException('Not implemented.');
    }

    public function createTemporaryLocalFile(Artifact $artifact): string
    {
        throw new LogicException('Not implemented.');
    }

    public function readContent(Artifact $artifact): string
    {
        throw new LogicException('Not implemented.');
    }
}
