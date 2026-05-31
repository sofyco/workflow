<?php declare(strict_types=1);

namespace Sofyco\Workflow\Domain\Enum;

enum WorkflowStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}
