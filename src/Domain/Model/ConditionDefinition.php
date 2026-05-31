<?php declare(strict_types=1);

namespace Sofyco\Workflow\Domain\Model;

use Sofyco\Workflow\Domain\Enum\ConditionOperator;

final readonly class ConditionDefinition
{
    public function __construct(
        public string            $field,
        public ConditionOperator $operator,
        public mixed             $value,
    )
    {
    }
}
