<?php declare(strict_types=1);

namespace Sofyco\Workflow\Domain\Model;

use DateTimeImmutable;

final class WorkflowVersion
{
    /** @var array<string, WorkflowNode> */
    private array $nodes = [];

    /** @var array<string, WorkflowEdge> */
    private array $edges = [];

    /** @var array<string, mixed> */
    private array $settings = [];

    public function __construct(
        private string             $id,
        private string             $workflowId,
        private int                $version,
        private string             $name,
        private string             $startNodeId,
        private DateTimeImmutable  $createdAt,
        private ?DateTimeImmutable $publishedAt = null,
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

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStartNodeId(): string
    {
        return $this->startNodeId;
    }

    /**
     * @return list<WorkflowNode>
     */
    public function getNodes(): array
    {
        return array_values($this->nodes);
    }

    public function getNode(string $nodeId): ?WorkflowNode
    {
        return $this->nodes[$nodeId] ?? null;
    }

    public function addNode(WorkflowNode $node): void
    {
        $this->nodes[$node->getId()] = $node;
    }

    /**
     * @return list<WorkflowEdge>
     */
    public function getEdges(): array
    {
        return array_values($this->edges);
    }

    public function getEdge(string $edgeId): ?WorkflowEdge
    {
        return $this->edges[$edgeId] ?? null;
    }

    public function addEdge(WorkflowEdge $edge): void
    {
        $this->edges[$edge->getId()] = $edge;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(DateTimeImmutable $publishedAt): void
    {
        $this->publishedAt = $publishedAt;
    }
}
