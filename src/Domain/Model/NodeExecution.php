<?php declare(strict_types=1);

namespace Sofyco\Workflow\Domain\Model;

use DateTimeImmutable;
use Sofyco\Workflow\Domain\Enum\NodeExecutionStatus;

final class NodeExecution
{
    /** @var list<ArtifactReference> */
    private array $inputArtifacts = [];

    /** @var list<ArtifactReference> */
    private array $outputArtifacts = [];

    /** @var array<string, mixed> */
    private array $outputData = [];

    public function __construct(
        private string              $id,
        private string              $workflowRunId,
        private string              $nodeId,
        private int                 $attempt,
        private string              $executionKey,
        private NodeExecutionStatus $status,
        private DateTimeImmutable   $createdAt,
        private ?DateTimeImmutable  $startedAt = null,
        private ?DateTimeImmutable  $finishedAt = null,
        private ?string             $error = null,
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

    public function getNodeId(): string
    {
        return $this->nodeId;
    }

    public function getAttempt(): int
    {
        return $this->attempt;
    }

    public function getExecutionKey(): string
    {
        return $this->executionKey;
    }

    public function getStatus(): NodeExecutionStatus
    {
        return $this->status;
    }

    public function setStatus(NodeExecutionStatus $status): void
    {
        $this->status = $status;
    }

    /**
     * @return list<ArtifactReference>
     */
    public function getInputArtifacts(): array
    {
        return $this->inputArtifacts;
    }

    /**
     * @param list<ArtifactReference> $inputArtifacts
     */
    public function setInputArtifacts(array $inputArtifacts): void
    {
        $this->inputArtifacts = $inputArtifacts;
    }

    /**
     * @return list<ArtifactReference>
     */
    public function getOutputArtifacts(): array
    {
        return $this->outputArtifacts;
    }

    /**
     * @param list<ArtifactReference> $outputArtifacts
     */
    public function setOutputArtifacts(array $outputArtifacts): void
    {
        $this->outputArtifacts = $outputArtifacts;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOutputData(): array
    {
        return $this->outputData;
    }

    /**
     * @param array<string, mixed> $outputData
     */
    public function setOutputData(array $outputData): void
    {
        $this->outputData = $outputData;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): void
    {
        $this->error = $error;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getStartedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?DateTimeImmutable $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function getFinishedAt(): ?DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?DateTimeImmutable $finishedAt): void
    {
        $this->finishedAt = $finishedAt;
    }

    public function isCompleted(): bool
    {
        return $this->status === NodeExecutionStatus::Completed;
    }

    public static function buildExecutionKey(string $workflowRunId, string $nodeId, int $attempt): string
    {
        return sprintf('%s:%s:%d', $workflowRunId, $nodeId, $attempt);
    }
}
