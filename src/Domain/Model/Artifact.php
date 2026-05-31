<?php declare(strict_types=1);

namespace Sofyco\Workflow\Domain\Model;

use DateTimeImmutable;
use Sofyco\Workflow\Domain\Enum\ArtifactType;

final class Artifact
{
    /** @var array<string, mixed> */
    private array $metadata = [];

    public function __construct(
        private string            $id,
        private string            $workflowRunId,
        private ?string           $nodeExecutionId,
        private ArtifactType      $type,
        private string            $alias,
        private string            $mimeType,
        private string            $extension,
        private string            $storagePath,
        private int               $sizeBytes,
        private DateTimeImmutable $createdAt,
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getWorkflowRunId(): string
    {
        return $this->workflowRunId;
    }

    public function getNodeExecutionId(): ?string
    {
        return $this->nodeExecutionId;
    }

    public function getType(): ArtifactType
    {
        return $this->type;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    public function getSizeBytes(): int
    {
        return $this->sizeBytes;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
