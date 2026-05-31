<?php declare(strict_types=1);

namespace Sofyco\Workflow\Domain\Model;

use DateTimeImmutable;
use Sofyco\Workflow\Domain\Enum\ExecutionEventType;

final class ExecutionEvent
{
    /** @var array<string, mixed> */
    private array $payload = [];

    public function __construct(
        private string             $id,
        private string             $workflowRunId,
        private ExecutionEventType $type,
        private DateTimeImmutable  $createdAt,
        private ?string            $nodeId = null,
        private ?string            $nodeExecutionId = null,
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

    public function getType(): ExecutionEventType
    {
        return $this->type;
    }

    public function getNodeId(): ?string
    {
        return $this->nodeId;
    }

    public function getNodeExecutionId(): ?string
    {
        return $this->nodeExecutionId;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
