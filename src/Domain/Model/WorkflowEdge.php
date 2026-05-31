<?php declare(strict_types=1);

namespace Sofyco\Workflow\Domain\Model;

final class WorkflowEdge
{
    public function __construct(
        private string               $id,
        private string               $fromNodeId,
        private string               $fromPort,
        private string               $toNodeId,
        private string               $toPort,
        private ?ConditionDefinition $condition = null,
        private int                  $priority = 0,
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFromNodeId(): string
    {
        return $this->fromNodeId;
    }

    public function getFromPort(): string
    {
        return $this->fromPort;
    }

    public function getToNodeId(): string
    {
        return $this->toNodeId;
    }

    public function getToPort(): string
    {
        return $this->toPort;
    }

    public function getCondition(): ?ConditionDefinition
    {
        return $this->condition;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
