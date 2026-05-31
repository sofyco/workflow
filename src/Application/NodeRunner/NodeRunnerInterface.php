<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\NodeRunner;

use Sofyco\Workflow\Domain\Enum\NodeType;

interface NodeRunnerInterface
{
    public function supports(NodeType $type): bool;

    public function run(NodeExecutionContext $context): NodeExecutionResult;
}
