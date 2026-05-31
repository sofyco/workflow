<?php declare(strict_types=1);

namespace Sofyco\Workflow\Infrastructure\Doctrine;

use LogicException;
use Sofyco\Workflow\Domain\Model\WorkflowRun;

final class WorkflowRunRepository implements WorkflowRunRepositoryInterface
{
    public function find(string $id): ?WorkflowRun
    {
        throw new LogicException('Not implemented.');
    }

    public function save(WorkflowRun $run): void
    {
        throw new LogicException('Not implemented.');
    }
}
