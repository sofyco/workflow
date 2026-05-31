<?php declare(strict_types=1);

namespace Sofyco\Workflow\Domain\Enum;

enum RetryStrategy: string
{
    case Fixed = 'fixed';
    case Exponential = 'exponential';
}
