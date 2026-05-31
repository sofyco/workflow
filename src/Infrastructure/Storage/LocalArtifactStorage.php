<?php declare(strict_types=1);

namespace Sofyco\Workflow\Infrastructure\Storage;

use DateTimeImmutable;
use RuntimeException;
use Sofyco\Workflow\Application\Artifact\ArtifactStorageInterface;
use Sofyco\Workflow\Application\Id\IdGeneratorInterface;
use Sofyco\Workflow\Domain\Enum\ArtifactType;
use Sofyco\Workflow\Domain\Model\Artifact;
use Sofyco\Workflow\Domain\Model\NodeExecution;
use Sofyco\Workflow\Domain\Model\WorkflowRun;

final readonly class LocalArtifactStorage implements ArtifactStorageInterface
{
    public function __construct(
        private string $basePath,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

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
        if (!is_readable($localPath)) {
            throw new RuntimeException(sprintf('Local file "%s" is not readable.', $localPath));
        }

        $content = file_get_contents($localPath);

        if ($content === false) {
            throw new RuntimeException(sprintf('Failed to read local file "%s".', $localPath));
        }

        return $this->persistContent(
            run: $run,
            execution: $execution,
            alias: $alias,
            content: $content,
            type: $type,
            mimeType: $mimeType,
            extension: $extension,
            metadata: $metadata,
        );
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
        return $this->persistContent(
            run: $run,
            execution: $execution,
            alias: $alias,
            content: $content,
            type: $type,
            mimeType: $mimeType,
            extension: $extension,
            metadata: $metadata,
        );
    }

    public function createTemporaryLocalFile(Artifact $artifact): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'workflow_artifact_');

        if ($tempPath === false) {
            throw new RuntimeException('Failed to create temporary file.');
        }

        $targetPath = $tempPath . '.' . $artifact->getExtension();
        rename($tempPath, $targetPath);

        file_put_contents($targetPath, $this->readContent($artifact));

        return $targetPath;
    }

    public function readContent(Artifact $artifact): string
    {
        $path = $this->resolveAbsolutePath($artifact);
        $content = file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException(sprintf('Failed to read artifact "%s".', $artifact->getId()));
        }

        return $content;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function persistContent(
        WorkflowRun $run,
        ?NodeExecution $execution,
        string $alias,
        string $content,
        ArtifactType $type,
        string $mimeType,
        string $extension,
        array $metadata,
    ): Artifact {
        $artifactId = $this->idGenerator->generate();
        $storagePath = $this->buildStoragePath(
            run: $run,
            execution: $execution,
            artifactId: $artifactId,
            alias: $alias,
            extension: $extension,
        );

        $absolutePath = $this->basePath . '/' . $storagePath;
        $directory = dirname($absolutePath);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Failed to create directory "%s".', $directory));
        }

        if (file_put_contents($absolutePath, $content) === false) {
            throw new RuntimeException(sprintf('Failed to write artifact to "%s".', $absolutePath));
        }

        $artifact = new Artifact(
            id: $artifactId,
            workflowRunId: $run->getId(),
            nodeExecutionId: $execution?->getId(),
            type: $type,
            alias: $alias,
            mimeType: $mimeType,
            extension: $extension,
            storagePath: $storagePath,
            sizeBytes: strlen($content),
            createdAt: new DateTimeImmutable(),
        );
        $artifact->setMetadata($metadata);

        return $artifact;
    }

    private function buildStoragePath(
        WorkflowRun $run,
        ?NodeExecution $execution,
        string $artifactId,
        string $alias,
        string $extension,
    ): string {
        $filename = sprintf('%s.%s', $alias, $extension);

        if ($execution === null) {
            return sprintf('workflow-runs/%s/input/%s/%s', $run->getId(), $artifactId, $filename);
        }

        return sprintf(
            'workflow-runs/%s/nodes/%s/%s/%s/%s',
            $run->getId(),
            $execution->getNodeId(),
            $execution->getId(),
            $artifactId,
            $filename,
        );
    }

    private function resolveAbsolutePath(Artifact $artifact): string
    {
        return $this->basePath . '/' . $artifact->getStoragePath();
    }
}
