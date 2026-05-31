<?php declare(strict_types=1);

namespace Sofyco\Workflow\Domain\Model;

use Sofyco\Workflow\Domain\Enum\RetryStrategy;

final readonly class RetryPolicy
{
    public function __construct(
        public int           $maxAttempts = 3,
        public int           $delaySeconds = 30,
        public RetryStrategy $strategy = RetryStrategy::Fixed,
    )
    {
    }
}
