<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Workflow;

use LogicException;
use Sofyco\Workflow\Domain\Model\Workflow;
use Sofyco\Workflow\Domain\Model\WorkflowEdge;
use Sofyco\Workflow\Domain\Model\WorkflowNode;
use Sofyco\Workflow\Domain\Model\WorkflowVersion;

final readonly class WorkflowService
{
    public function create(string $userId, string $name): Workflow
    {
        throw new LogicException('Not implemented.');
    }

    public function addNode(string $workflowId, WorkflowNode $node): void
    {
        throw new LogicException('Not implemented.');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateNode(string $workflowId, string $nodeId, array $data): void
    {
        throw new LogicException('Not implemented.');
    }

    public function removeNode(string $workflowId, string $nodeId): void
    {
        throw new LogicException('Not implemented.');
    }

    public function addEdge(string $workflowId, WorkflowEdge $edge): void
    {
        throw new LogicException('Not implemented.');
    }

    public function removeEdge(string $workflowId, string $edgeId): void
    {
        throw new LogicException('Not implemented.');
    }

    public function publish(string $workflowId): WorkflowVersion
    {
        throw new LogicException('Not implemented.');
    }
}
