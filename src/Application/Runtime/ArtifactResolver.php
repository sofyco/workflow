<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Runtime;

use Sofyco\Workflow\Domain\Model\Artifact;
use Sofyco\Workflow\Domain\Model\NodeExecution;
use Sofyco\Workflow\Domain\Model\WorkflowNode;
use Sofyco\Workflow\Domain\Model\WorkflowRun;
use Sofyco\Workflow\Domain\Model\WorkflowVersion;
use Sofyco\Workflow\Domain\Enum\NodeExecutionStatus;
use Sofyco\Workflow\Infrastructure\Doctrine\ArtifactRepositoryInterface;
use Sofyco\Workflow\Infrastructure\Doctrine\NodeExecutionRepositoryInterface;

final readonly class ArtifactResolver
{
    public function __construct(
        private NodeExecutionRepositoryInterface $nodeExecutions,
        private ArtifactRepositoryInterface $artifacts,
    ) {
    }

    /**
     * @return array<string, list<Artifact>>
     */
    public function resolveInputs(WorkflowRun $run, WorkflowVersion $version, WorkflowNode $node): array
    {
        $resolved = [];

        foreach ($version->getEdges() as $edge) {
            if ($edge->getToNodeId() !== $node->getId()) {
                continue;
            }

            $artifact = $this->findLatestOutputArtifact(
                run: $run,
                fromNodeId: $edge->getFromNodeId(),
                fromPort: $edge->getFromPort(),
            );

            if ($artifact === null) {
                continue;
            }

            $resolved[$edge->getToPort()] ??= [];
            $resolved[$edge->getToPort()][] = $artifact;
        }

        if ($node->getType()->value === 'input') {
            foreach ($run->getInputArtifacts() as $reference) {
                $artifact = $this->artifacts->find($reference->artifactId);

                if ($artifact === null) {
                    continue;
                }

                $resolved[$reference->alias] ??= [];
                $resolved[$reference->alias][] = $artifact;
            }
        }

        return $resolved;
    }

    public function hasCompatibleArtifactForPort(
        WorkflowRun $run,
        WorkflowVersion $version,
        WorkflowNode $node,
        \Sofyco\Workflow\Domain\Model\WorkflowPort $port,
    ): bool {
        $artifacts = $this->resolveInputs($run, $version, $node)[$port->name] ?? [];

        if ($artifacts === []) {
            return false;
        }

        foreach ($artifacts as $artifact) {
            if ($this->isCompatible($artifact, $port)) {
                return true;
            }
        }

        return false;
    }

    private function findLatestOutputArtifact(
        WorkflowRun $run,
        string $fromNodeId,
        string $fromPort,
    ): ?Artifact {
        $executions = $this->nodeExecutions->findByWorkflowRunIdAndNodeId($run->getId(), $fromNodeId);

        usort(
            $executions,
            static fn (NodeExecution $a, NodeExecution $b): int => $b->getCreatedAt() <=> $a->getCreatedAt(),
        );

        foreach ($executions as $execution) {
            if ($execution->getStatus() !== NodeExecutionStatus::Completed) {
                continue;
            }

            foreach ($execution->getOutputArtifacts() as $reference) {
                if ($reference->alias !== $fromPort) {
                    continue;
                }

                return $this->artifacts->find($reference->artifactId);
            }
        }

        return null;
    }

    private function isCompatible(Artifact $artifact, \Sofyco\Workflow\Domain\Model\WorkflowPort $port): bool
    {
        if ($artifact->getType() !== $port->type) {
            return false;
        }

        if ($port->allowedMimeTypes === []) {
            return true;
        }

        return in_array($artifact->getMimeType(), $port->allowedMimeTypes, true);
    }
}
