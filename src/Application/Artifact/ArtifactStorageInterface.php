<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Artifact;

use Sofyco\Workflow\Domain\Enum\ArtifactType;
use Sofyco\Workflow\Domain\Model\Artifact;
use Sofyco\Workflow\Domain\Model\NodeExecution;
use Sofyco\Workflow\Domain\Model\WorkflowRun;

interface ArtifactStorageInterface
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function storeFile(
        WorkflowRun    $run,
        ?NodeExecution $execution,
        string         $alias,
        string         $localPath,
        ArtifactType   $type,
        string         $mimeType,
        string         $extension,
        array          $metadata = [],
    ): Artifact;

    /**
     * @param array<string, mixed> $metadata
     */
    public function storeContent(
        WorkflowRun    $run,
        ?NodeExecution $execution,
        string         $alias,
        string         $content,
        ArtifactType   $type,
        string         $mimeType,
        string         $extension,
        array          $metadata = [],
    ): Artifact;

    public function createTemporaryLocalFile(Artifact $artifact): string;

    public function readContent(Artifact $artifact): string;
}
