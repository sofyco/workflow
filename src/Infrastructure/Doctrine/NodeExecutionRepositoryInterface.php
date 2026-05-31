<?php declare(strict_types=1);

namespace Sofyco\Workflow\Infrastructure\Doctrine;

use Sofyco\Workflow\Domain\Model\NodeExecution;

interface NodeExecutionRepositoryInterface
{
    public function find(string $id): ?NodeExecution;

    public function findByExecutionKey(string $executionKey): ?NodeExecution;

    /**
     * @return list<NodeExecution>
     */
    public function findByWorkflowRunId(string $workflowRunId): array;

    /**
     * @return list<NodeExecution>
     */
    public function findByWorkflowRunIdAndNodeId(string $workflowRunId, string $nodeId): array;

    public function save(NodeExecution $execution): void;
}
