<?php declare(strict_types=1);

namespace Sofyco\Workflow\Infrastructure\Doctrine;

use Sofyco\Workflow\Domain\Model\WorkflowRun;

interface WorkflowRunRepositoryInterface
{
    public function find(string $id): ?WorkflowRun;

    public function save(WorkflowRun $run): void;
}
