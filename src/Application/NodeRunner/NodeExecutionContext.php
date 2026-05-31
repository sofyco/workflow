<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\NodeRunner;

use Sofyco\Workflow\Domain\Model\NodeExecution;
use Sofyco\Workflow\Domain\Model\WorkflowNode;
use Sofyco\Workflow\Domain\Model\WorkflowRun;
use Sofyco\Workflow\Domain\Model\WorkflowVersion;

final readonly class NodeExecutionContext
{
    /**
     * @param array<string, list<\Sofyco\Workflow\Domain\Model\Artifact>> $inputArtifacts
     * @param list<NodeExecution> $previousExecutions
     * @param array<string, mixed> $variables
     */
    public function __construct(
        public WorkflowRun $run,
        public WorkflowVersion $version,
        public WorkflowNode $node,
        public NodeExecution $execution,
        public array $inputArtifacts,
        public array $previousExecutions,
        public array $variables,
    ) {
    }
}
