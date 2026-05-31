<?php declare(strict_types=1);

namespace Sofyco\Workflow\Infrastructure\Doctrine;

use LogicException;
use Sofyco\Workflow\Domain\Model\WorkflowVersion;

final class WorkflowVersionRepository implements WorkflowVersionRepositoryInterface
{
    public function find(string $id): ?WorkflowVersion
    {
        throw new LogicException('Not implemented.');
    }

    public function findLatestByWorkflowId(string $workflowId): ?WorkflowVersion
    {
        throw new LogicException('Not implemented.');
    }

    public function save(WorkflowVersion $version): void
    {
        throw new LogicException('Not implemented.');
    }
}
