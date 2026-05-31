<?php declare(strict_types=1);

namespace Sofyco\Workflow\Infrastructure\Messenger;

use Sofyco\Workflow\Application\Runtime\WorkflowExecutionService;
use Sofyco\Workflow\Application\Runtime\WorkflowScheduler;

final readonly class ExecuteWorkflowNodeHandler
{
    public function __construct(
        private WorkflowExecutionService $executionService,
        private WorkflowScheduler        $scheduler,
    )
    {
    }

    public function __invoke(ExecuteWorkflowNode $message): void
    {
        $this->executionService->execute(
            workflowRunId: $message->workflowRunId,
            nodeId: $message->nodeId,
            attempt: $message->attempt,
        );

        $this->scheduler->scheduleReadyNodes($message->workflowRunId);
    }
}
