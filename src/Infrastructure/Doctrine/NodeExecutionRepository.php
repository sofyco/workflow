<?php declare(strict_types=1);

namespace Sofyco\Workflow\Infrastructure\Doctrine;

use LogicException;
use Sofyco\Workflow\Domain\Model\NodeExecution;

final class NodeExecutionRepository implements NodeExecutionRepositoryInterface
{
    public function find(string $id): ?NodeExecution
    {
        throw new LogicException('Not implemented.');
    }

    public function findByExecutionKey(string $executionKey): ?NodeExecution
    {
        throw new LogicException('Not implemented.');
    }

    /**
     * @return list<NodeExecution>
     */
    public function findByWorkflowRunId(string $workflowRunId): array
    {
        throw new LogicException('Not implemented.');
    }

    /**
     * @return list<NodeExecution>
     */
    public function findByWorkflowRunIdAndNodeId(string $workflowRunId, string $nodeId): array
    {
        throw new LogicException('Not implemented.');
    }

    public function save(NodeExecution $execution): void
    {
        throw new LogicException('Not implemented.');
    }
}
