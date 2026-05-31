<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Runtime;

use DateTimeImmutable;
use Sofyco\Workflow\Domain\Model\WorkflowNode;
use Sofyco\Workflow\Domain\Model\WorkflowRun;
use Sofyco\Workflow\Domain\Model\WorkflowVersion;
use Sofyco\Workflow\Domain\Enum\NodeExecutionStatus;
use Sofyco\Workflow\Domain\Enum\NodeType;
use Sofyco\Workflow\Domain\Enum\WorkflowRunStatus;
use Sofyco\Workflow\Infrastructure\Doctrine\NodeExecutionRepositoryInterface;

final readonly class CompletionResolver
{
    public function __construct(
        private NodeExecutionRepositoryInterface $nodeExecutions,
    ) {
    }

    public function resolve(WorkflowRun $run, WorkflowVersion $version): void
    {
        if ($this->hasRunningExecutions($run->getId())) {
            return;
        }

        $finalOutputNodes = array_filter(
            $version->getNodes(),
            static fn (WorkflowNode $node): bool => $node->getType() === NodeType::FinalOutput,
        );

        foreach ($finalOutputNodes as $node) {
            if ($this->hasCompletedExecution($run->getId(), $node->getId())) {
                $run->setStatus(WorkflowRunStatus::Completed);
                $run->setFinishedAt(new DateTimeImmutable());

                return;
            }
        }

        if ($this->hasFailedExecutions($run->getId())) {
            $run->setStatus(WorkflowRunStatus::Failed);
            $run->setFinishedAt(new DateTimeImmutable());
            $run->setError('One or more required nodes failed.');
        }
    }

    private function hasRunningExecutions(string $workflowRunId): bool
    {
        foreach ($this->nodeExecutions->findByWorkflowRunId($workflowRunId) as $execution) {
            if ($execution->getStatus() === NodeExecutionStatus::Running) {
                return true;
            }
        }

        return false;
    }

    private function hasCompletedExecution(string $workflowRunId, string $nodeId): bool
    {
        foreach ($this->nodeExecutions->findByWorkflowRunIdAndNodeId($workflowRunId, $nodeId) as $execution) {
            if ($execution->getStatus() === NodeExecutionStatus::Completed) {
                return true;
            }
        }

        return false;
    }

    private function hasFailedExecutions(string $workflowRunId): bool
    {
        foreach ($this->nodeExecutions->findByWorkflowRunId($workflowRunId) as $execution) {
            if ($execution->getStatus() === NodeExecutionStatus::Failed) {
                return true;
            }
        }

        return false;
    }
}
