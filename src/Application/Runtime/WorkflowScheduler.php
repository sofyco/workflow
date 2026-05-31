<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Runtime;

use RuntimeException;
use Sofyco\Workflow\Domain\Model\WorkflowNode;
use Sofyco\Workflow\Domain\Enum\NodeExecutionStatus;
use Sofyco\Workflow\Infrastructure\Doctrine\NodeExecutionRepositoryInterface;
use Sofyco\Workflow\Infrastructure\Doctrine\WorkflowRunRepositoryInterface;
use Sofyco\Workflow\Infrastructure\Doctrine\WorkflowVersionRepositoryInterface;

final readonly class WorkflowScheduler
{
    public function __construct(
        private WorkflowRunRepositoryInterface $runs,
        private WorkflowVersionRepositoryInterface $versions,
        private NodeExecutionRepositoryInterface $nodeExecutions,
        private NodeReadinessResolver $nodeReadinessResolver,
        private TransitionResolver $transitionResolver,
        private WorkflowExecutionService $executionService,
        private CompletionResolver $completionResolver,
    ) {
    }

    public function scheduleReadyNodes(string $workflowRunId): int
    {
        $run = $this->runs->find($workflowRunId);

        if ($run === null) {
            throw new RuntimeException(sprintf('Workflow run "%s" not found.', $workflowRunId));
        }

        $version = $this->versions->find($run->getWorkflowVersionId());

        if ($version === null) {
            throw new RuntimeException(sprintf('Workflow version "%s" not found.', $run->getWorkflowVersionId()));
        }

        $scheduled = 0;

        foreach ($version->getNodes() as $node) {
            if (!$this->canSchedule($run, $version, $node)) {
                continue;
            }

            $attempt = $this->resolveNextAttempt($workflowRunId, $node->getId());
            $this->executionService->execute($workflowRunId, $node->getId(), $attempt);
            ++$scheduled;
        }

        if ($scheduled === 0) {
            $this->completionResolver->resolve($run, $version);
            $this->runs->save($run);
        }

        return $scheduled;
    }

    private function canSchedule(
        \Sofyco\Workflow\Domain\Model\WorkflowRun $run,
        \Sofyco\Workflow\Domain\Model\WorkflowVersion $version,
        WorkflowNode $node,
    ): bool {
        if ($this->hasActiveExecution($run->getId(), $node->getId())) {
            return false;
        }

        if ($this->hasCompletedExecution($run->getId(), $node->getId())) {
            return false;
        }

        if (!$this->transitionResolver->isReachable($run, $version, $node->getId(), $this->nodeExecutions)) {
            return false;
        }

        return $this->nodeReadinessResolver->isReady($run, $version, $node);
    }

    private function hasActiveExecution(string $workflowRunId, string $nodeId): bool
    {
        foreach ($this->nodeExecutions->findByWorkflowRunIdAndNodeId($workflowRunId, $nodeId) as $execution) {
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

    private function resolveNextAttempt(string $workflowRunId, string $nodeId): int
    {
        $executions = $this->nodeExecutions->findByWorkflowRunIdAndNodeId($workflowRunId, $nodeId);

        return count($executions) + 1;
    }
}
