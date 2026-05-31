<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Workflow;

use LogicException;
use Sofyco\Workflow\Domain\Model\WorkflowVersion;

final readonly class WorkflowValidator
{
    public function validate(WorkflowVersion $version): void
    {
        throw new LogicException('Not implemented.');
    }
}
