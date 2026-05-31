<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Runtime;

use Sofyco\Workflow\Application\Artifact\ArtifactContentReader;
use Sofyco\Workflow\Domain\Model\Artifact;
use Sofyco\Workflow\Domain\Model\NodeExecution;
use Sofyco\Workflow\Domain\Model\WorkflowRun;
use Sofyco\Workflow\Domain\Model\WorkflowVersion;
use Sofyco\Workflow\Domain\Enum\NodeExecutionStatus;
use Sofyco\Workflow\Infrastructure\Doctrine\ArtifactRepositoryInterface;
use Sofyco\Workflow\Infrastructure\Doctrine\NodeExecutionRepositoryInterface;

final readonly class RuntimeContextBuilder
{
    public function __construct(
        private NodeExecutionRepositoryInterface $nodeExecutions,
        private ArtifactRepositoryInterface $artifacts,
        private ArtifactContentReader $artifactContentReader,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(WorkflowRun $run, WorkflowVersion $version): array
    {
        $context = [
            'input' => $this->buildInputContext($run),
            'nodes' => [],
        ];

        foreach ($this->nodeExecutions->findByWorkflowRunId($run->getId()) as $execution) {
            if ($execution->getStatus() !== NodeExecutionStatus::Completed) {
                continue;
            }

            $node = $version->getNode($execution->getNodeId());

            if ($node === null) {
                continue;
            }

            $nodeKey = $this->normalizeNodeKey($node->getId());
            $context['nodes'][$nodeKey] ??= ['latest' => ['output' => []]];

            foreach ($execution->getOutputArtifacts() as $reference) {
                $artifact = $this->artifacts->find($reference->artifactId);

                if ($artifact === null) {
                    continue;
                }

                $context['nodes'][$nodeKey]['latest']['output'][$reference->alias] = $this->serializeArtifact($artifact);
            }
        }

        return $context;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildInputContext(WorkflowRun $run): array
    {
        $input = [];

        foreach ($run->getInputArtifacts() as $reference) {
            $artifact = $this->artifacts->find($reference->artifactId);

            if ($artifact === null) {
                continue;
            }

            $input[$reference->alias] = $this->serializeArtifact($artifact);
        }

        return $input;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeArtifact(Artifact $artifact): array
    {
        $data = [
            'artifactId' => $artifact->getId(),
            'type' => $artifact->getType()->value,
            'mimeType' => $artifact->getMimeType(),
            'metadata' => $artifact->getMetadata(),
        ];

        if ($this->artifactContentReader->canReadAsText($artifact)) {
            $data['content'] = $artifact->getMimeType() === 'application/json'
                ? $this->artifactContentReader->readJson($artifact)
                : $this->artifactContentReader->readText($artifact);
        }

        return $data;
    }

    private function normalizeNodeKey(string $nodeId): string
    {
        return str_replace('-', '_', $nodeId);
    }
}
