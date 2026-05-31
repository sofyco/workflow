<?php declare(strict_types=1);

namespace Sofyco\Workflow\Infrastructure\InMemory;

use Sofyco\Workflow\Domain\Model\WorkflowRun;
use Sofyco\Workflow\Infrastructure\Doctrine\WorkflowRunRepositoryInterface;

final class InMemoryWorkflowRunRepository implements WorkflowRunRepositoryInterface
{
    /** @var array<string, WorkflowRun> */
    private array $runs = [];

    public function find(string $id): ?WorkflowRun
    {
        return $this->runs[$id] ?? null;
    }

    public function save(WorkflowRun $run): void
    {
        $this->runs[$run->getId()] = $run;
    }
}
