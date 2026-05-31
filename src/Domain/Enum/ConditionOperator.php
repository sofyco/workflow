<?php declare(strict_types=1);

namespace Sofyco\Workflow\Domain\Enum;

enum ConditionOperator: string
{
    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case GreaterThan = 'greater_than';
    case LessThan = 'less_than';
    case Contains = 'contains';
    case Exists = 'exists';
}
