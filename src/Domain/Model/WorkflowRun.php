<?php declare(strict_types=1);

namespace Sofyco\Workflow\Domain\Model;

use DateTimeImmutable;
use Sofyco\Workflow\Domain\Enum\WorkflowRunStatus;

final class WorkflowRun
{
    /** @var list<ArtifactReference> */
    private array $inputArtifacts = [];

    /** @var array<string, mixed> */
    private array $context = [];

    public function __construct(
        private string             $id,
        private string             $workflowId,
        private string             $workflowVersionId,
        private int                $workflowVersion,
        private WorkflowRunStatus  $status,
        private DateTimeImmutable  $createdAt,
        private ?DateTimeImmutable $startedAt = null,
        private ?DateTimeImmutable $finishedAt = null,
        private ?string            $error = null,
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getWorkflowId(): string
    {
        return $this->workflowId;
    }

    public function getWorkflowVersionId(): string
    {
        return $this->workflowVersionId;
    }

    public function getWorkflowVersion(): int
    {
        return $this->workflowVersion;
    }

    public function getStatus(): WorkflowRunStatus
    {
        return $this->status;
    }

    public function setStatus(WorkflowRunStatus $status): void
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
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
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

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): void
    {
        $this->error = $error;
    }
}
