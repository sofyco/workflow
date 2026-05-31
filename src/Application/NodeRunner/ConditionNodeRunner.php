<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\NodeRunner;

use LogicException;
use Sofyco\Workflow\Domain\Enum\NodeType;

final readonly class ConditionNodeRunner implements NodeRunnerInterface
{
    public function supports(NodeType $type): bool
    {
        return $type === NodeType::Condition;
    }

    public function run(NodeExecutionContext $context): NodeExecutionResult
    {
        throw new LogicException('Not implemented.');
    }
}
