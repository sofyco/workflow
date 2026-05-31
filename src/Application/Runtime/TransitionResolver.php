<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Runtime;

use Sofyco\Workflow\Domain\Model\WorkflowRun;
use Sofyco\Workflow\Domain\Model\WorkflowVersion;
use Sofyco\Workflow\Domain\Enum\NodeExecutionStatus;
use Sofyco\Workflow\Infrastructure\Doctrine\NodeExecutionRepositoryInterface;

final readonly class TransitionResolver
{
    public function __construct(
        private ConditionEvaluator $conditionEvaluator,
    ) {
    }

    public function isTransitionAllowed(
        WorkflowRun $run,
        WorkflowVersion $version,
        string $fromNodeId,
        string $toNodeId,
    ): bool {
        foreach ($version->getEdges() as $edge) {
            if ($edge->getFromNodeId() !== $fromNodeId || $edge->getToNodeId() !== $toNodeId) {
                continue;
            }

            if ($edge->getCondition() === null) {
                return true;
            }

            if ($this->conditionEvaluator->matches($edge->getCondition(), $run, $run->getContext())) {
                return true;
            }
        }

        return false;
    }

    public function isReachable(
        WorkflowRun $run,
        WorkflowVersion $version,
        string $nodeId,
        NodeExecutionRepositoryInterface $nodeExecutions,
    ): bool {
        if ($nodeId === $version->getStartNodeId()) {
            return true;
        }

        foreach ($version->getEdges() as $edge) {
            if ($edge->getToNodeId() !== $nodeId) {
                continue;
            }

            if (!$this->hasCompletedExecution($nodeExecutions, $run->getId(), $edge->getFromNodeId())) {
                continue;
            }

            if ($edge->getCondition() !== null
                && !$this->conditionEvaluator->matches($edge->getCondition(), $run, $run->getContext())) {
                continue;
            }

            if ($this->isReachable($run, $version, $edge->getFromNodeId(), $nodeExecutions)) {
                return true;
            }
        }

        return false;
    }

    private function hasCompletedExecution(
        NodeExecutionRepositoryInterface $nodeExecutions,
        string $workflowRunId,
        string $nodeId,
    ): bool {
        foreach ($nodeExecutions->findByWorkflowRunIdAndNodeId($workflowRunId, $nodeId) as $execution) {
            if ($execution->getStatus() === NodeExecutionStatus::Completed) {
                return true;
            }
        }

        return false;
    }
}
