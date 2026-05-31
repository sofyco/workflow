<?php declare(strict_types=1);

namespace Sofyco\Workflow\Domain\Enum;

enum NodeExecutionStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Skipped = 'skipped';
    case Cancelled = 'cancelled';
}
