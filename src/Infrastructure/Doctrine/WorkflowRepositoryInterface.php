<?php declare(strict_types=1);

namespace Sofyco\Workflow\Infrastructure\Doctrine;

use Sofyco\Workflow\Domain\Model\Workflow;

interface WorkflowRepositoryInterface
{
    public function find(string $id): ?Workflow;

    public function save(Workflow $workflow): void;
}
