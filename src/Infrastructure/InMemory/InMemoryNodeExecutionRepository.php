<?php declare(strict_types=1);

namespace Sofyco\Workflow\Infrastructure\InMemory;

use Sofyco\Workflow\Domain\Model\NodeExecution;
use Sofyco\Workflow\Infrastructure\Doctrine\NodeExecutionRepositoryInterface;

final class InMemoryNodeExecutionRepository implements NodeExecutionRepositoryInterface
{
    /** @var array<string, NodeExecution> */
    private array $executions = [];

    public function find(string $id): ?NodeExecution
    {
        return $this->executions[$id] ?? null;
    }

    public function findByExecutionKey(string $executionKey): ?NodeExecution
    {
        foreach ($this->executions as $execution) {
            if ($execution->getExecutionKey() === $executionKey) {
                return $execution;
            }
        }

        return null;
    }

    /**
     * @return list<NodeExecution>
     */
    public function findByWorkflowRunId(string $workflowRunId): array
    {
        return array_values(array_filter(
            $this->executions,
            static fn (NodeExecution $execution): bool => $execution->getWorkflowRunId() === $workflowRunId,
        ));
    }

    /**
     * @return list<NodeExecution>
     */
    public function findByWorkflowRunIdAndNodeId(string $workflowRunId, string $nodeId): array
    {
        return array_values(array_filter(
            $this->findByWorkflowRunId($workflowRunId),
            static fn (NodeExecution $execution): bool => $execution->getNodeId() === $nodeId,
        ));
    }

    public function save(NodeExecution $execution): void
    {
        $this->executions[$execution->getId()] = $execution;
    }
}
