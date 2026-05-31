<?php declare(strict_types=1);

namespace Sofyco\Workflow\Infrastructure\Doctrine;

use Sofyco\Workflow\Domain\Model\ExecutionEvent;

interface ExecutionEventRepositoryInterface
{
    /**
     * @return list<ExecutionEvent>
     */
    public function findByWorkflowRunId(string $workflowRunId): array;

    public function save(ExecutionEvent $event): void;
}
