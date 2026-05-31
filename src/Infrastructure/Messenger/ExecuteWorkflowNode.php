<?php declare(strict_types=1);

namespace Sofyco\Workflow\Infrastructure\Messenger;

final readonly class ExecuteWorkflowNode
{
    public function __construct(
        public string $workflowRunId,
        public string $nodeId,
        public int    $attempt,
    )
    {
    }
}
