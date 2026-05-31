<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Runtime;

use Sofyco\Workflow\Domain\Enum\ConditionOperator;
use Sofyco\Workflow\Domain\Model\ConditionDefinition;
use Sofyco\Workflow\Domain\Model\WorkflowRun;

final readonly class ConditionEvaluator
{
    public function __construct(
        private FieldAccessor $fieldAccessor,
    )
    {
    }

    /**
     * @param array<string, mixed> $runtimeContext
     */
    public function matches(
        ConditionDefinition $condition,
        WorkflowRun         $run,
        array               $runtimeContext,
    ): bool
    {
        $actualValue = $this->fieldAccessor->get($runtimeContext, $condition->field);

        return match ($condition->operator) {
            ConditionOperator::Equals => $actualValue === $condition->value,
            ConditionOperator::NotEquals => $actualValue !== $condition->value,
            ConditionOperator::GreaterThan => is_numeric($actualValue) && is_numeric($condition->value)
                && $actualValue > $condition->value,
            ConditionOperator::LessThan => is_numeric($actualValue) && is_numeric($condition->value)
                && $actualValue < $condition->value,
            ConditionOperator::Contains => is_scalar($actualValue) || $actualValue === null
                ? str_contains((string) $actualValue, is_scalar($condition->value) ? (string) $condition->value : '')
                : false,
            ConditionOperator::Exists => $actualValue !== null,
        };
    }
}
