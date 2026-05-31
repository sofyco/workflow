<?php declare(strict_types=1);

namespace Sofyco\Workflow\Infrastructure\Doctrine;

use LogicException;
use Sofyco\Workflow\Domain\Model\ExecutionEvent;

final class ExecutionEventRepository implements ExecutionEventRepositoryInterface
{
    /**
     * @return list<ExecutionEvent>
     */
    public function findByWorkflowRunId(string $workflowRunId): array
    {
        throw new LogicException('Not implemented.');
    }

    public function save(ExecutionEvent $event): void
    {
        throw new LogicException('Not implemented.');
    }
}
