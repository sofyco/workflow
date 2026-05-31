<?php declare(strict_types=1);

namespace Sofyco\Workflow\Domain\Model;

final readonly class Position
{
    public function __construct(
        public float $x,
        public float $y,
    )
    {
    }
}
