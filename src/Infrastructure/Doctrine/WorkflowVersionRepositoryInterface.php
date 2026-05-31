<?php declare(strict_types=1);

namespace Sofyco\Workflow\Infrastructure\Doctrine;

use Sofyco\Workflow\Domain\Model\WorkflowVersion;

interface WorkflowVersionRepositoryInterface
{
    public function find(string $id): ?WorkflowVersion;

    public function findLatestByWorkflowId(string $workflowId): ?WorkflowVersion;

    public function save(WorkflowVersion $version): void;
}
