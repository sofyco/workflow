<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Runtime;

use Sofyco\Workflow\Domain\Model\WorkflowNode;
use Sofyco\Workflow\Domain\Model\WorkflowRun;
use Sofyco\Workflow\Domain\Model\WorkflowVersion;

final readonly class NodeReadinessResolver
{
    public function __construct(
        private ArtifactResolver $artifactResolver,
    )
    {
    }

    public function isReady(WorkflowRun $run, WorkflowVersion $version, WorkflowNode $node): bool
    {
        foreach ($node->getInputPorts() as $port) {
            if (!$port->required) {
                continue;
            }

            if (!$this->artifactResolver->hasCompatibleArtifactForPort($run, $version, $node, $port)) {
                return false;
            }
        }

        return true;
    }
}
