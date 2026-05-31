<?php declare(strict_types=1);

namespace Sofyco\Workflow\Infrastructure\Doctrine;

use LogicException;
use Sofyco\Workflow\Domain\Model\Workflow;

final class WorkflowRepository implements WorkflowRepositoryInterface
{
    public function find(string $id): ?Workflow
    {
        throw new LogicException('Not implemented.');
    }

    public function save(Workflow $workflow): void
    {
        throw new LogicException('Not implemented.');
    }
}
