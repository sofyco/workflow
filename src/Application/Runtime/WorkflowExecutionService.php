<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Runtime;

use DateTimeImmutable;
use RuntimeException;
use Sofyco\Workflow\Application\Artifact\ArtifactStorageInterface;
use Sofyco\Workflow\Application\Id\IdGeneratorInterface;
use Sofyco\Workflow\Application\NodeRunner\NodeExecutionContext;
use Sofyco\Workflow\Application\NodeRunner\NodeRunnerRegistry;
use Sofyco\Workflow\Domain\Model\ArtifactReference;
use Sofyco\Workflow\Domain\Model\NodeExecution;
use Sofyco\Workflow\Domain\Enum\ExecutionEventType;
use Sofyco\Workflow\Domain\Enum\NodeExecutionStatus;
use Sofyco\Workflow\Infrastructure\Doctrine\ArtifactRepositoryInterface;
use Sofyco\Workflow\Infrastructure\Doctrine\NodeExecutionRepositoryInterface;
use Sofyco\Workflow\Infrastructure\Doctrine\WorkflowRunRepositoryInterface;
use Sofyco\Workflow\Infrastructure\Doctrine\WorkflowVersionRepositoryInterface;

final readonly class WorkflowExecutionService
{
    public function __construct(
        private WorkflowRunRepositoryInterface $runs,
        private WorkflowVersionRepositoryInterface $versions,
        private NodeExecutionRepositoryInterface $nodeExecutions,
        private ArtifactRepositoryInterface $artifacts,
        private NodeRunnerRegistry $nodeRunners,
        private ArtifactResolver $artifactResolver,
        private RuntimeContextBuilder $runtimeContextBuilder,
        private ExecutionEventRecorder $eventRecorder,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function execute(string $workflowRunId, string $nodeId, int $attempt): NodeExecution
    {
        $executionKey = NodeExecution::buildExecutionKey($workflowRunId, $nodeId, $attempt);
        $existingExecution = $this->nodeExecutions->findByExecutionKey($executionKey);

        if ($existingExecution !== null && $existingExecution->isCompleted()) {
            return $existingExecution;
        }

        $run = $this->runs->find($workflowRunId);

        if ($run === null) {
            throw new RuntimeException(sprintf('Workflow run "%s" not found.', $workflowRunId));
        }

        $version = $this->versions->find($run->getWorkflowVersionId());

        if ($version === null) {
            throw new RuntimeException(sprintf('Workflow version "%s" not found.', $run->getWorkflowVersionId()));
        }

        $node = $version->getNode($nodeId);

        if ($node === null) {
            throw new RuntimeException(sprintf('Node "%s" not found.', $nodeId));
        }

        $execution = $existingExecution ?? new NodeExecution(
            id: $this->idGenerator->generate(),
            workflowRunId: $workflowRunId,
            nodeId: $nodeId,
            attempt: $attempt,
            executionKey: $executionKey,
            status: NodeExecutionStatus::Running,
            createdAt: new DateTimeImmutable(),
            startedAt: new DateTimeImmutable(),
        );

        if ($existingExecution === null) {
            $this->nodeExecutions->save($execution);
        } else {
            $execution->setStatus(NodeExecutionStatus::Running);
            $execution->setStartedAt(new DateTimeImmutable());
        }

        $this->eventRecorder->record(
            workflowRunId: $workflowRunId,
            type: ExecutionEventType::NodeStarted,
            nodeId: $nodeId,
            nodeExecutionId: $execution->getId(),
        );

        try {
            $inputArtifacts = $this->artifactResolver->resolveInputs($run, $version, $node);
            $previousExecutions = $this->nodeExecutions->findByWorkflowRunIdAndNodeId($workflowRunId, $nodeId);
            $variables = $this->runtimeContextBuilder->build($run, $version);
            $run->setContext($variables);
            $this->runs->save($run);

            $runner = $this->nodeRunners->get($node->getType());
            $result = $runner->run(new NodeExecutionContext(
                run: $run,
                version: $version,
                node: $node,
                execution: $execution,
                inputArtifacts: $inputArtifacts,
                previousExecutions: $previousExecutions,
                variables: $variables,
            ));

            $outputReferences = [];

            foreach ($result->outputArtifacts as $artifact) {
                $this->artifacts->save($artifact);
                $outputReferences[] = new ArtifactReference(
                    artifactId: $artifact->getId(),
                    alias: $artifact->getAlias(),
                    type: $artifact->getType(),
                    mimeType: $artifact->getMimeType(),
                );

                $this->eventRecorder->record(
                    workflowRunId: $workflowRunId,
                    type: ExecutionEventType::ArtifactCreated,
                    payload: ['artifactId' => $artifact->getId(), 'alias' => $artifact->getAlias()],
                    nodeId: $nodeId,
                    nodeExecutionId: $execution->getId(),
                );
            }

            $inputReferences = [];

            foreach ($inputArtifacts as $portArtifacts) {
                foreach ($portArtifacts as $artifact) {
                    $inputReferences[] = new ArtifactReference(
                        artifactId: $artifact->getId(),
                        alias: $artifact->getAlias(),
                        type: $artifact->getType(),
                        mimeType: $artifact->getMimeType(),
                    );
                }
            }

            $execution->setInputArtifacts($inputReferences);
            $execution->setOutputArtifacts($outputReferences);
            $execution->setOutputData($result->outputData);
            $execution->setStatus(NodeExecutionStatus::Completed);
            $execution->setFinishedAt(new DateTimeImmutable());
            $this->nodeExecutions->save($execution);

            $run->setContext($this->runtimeContextBuilder->build($run, $version));
            $this->runs->save($run);

            $this->eventRecorder->record(
                workflowRunId: $workflowRunId,
                type: ExecutionEventType::NodeCompleted,
                nodeId: $nodeId,
                nodeExecutionId: $execution->getId(),
            );
        } catch (\Throwable $exception) {
            $execution->setStatus(NodeExecutionStatus::Failed);
            $execution->setError($exception->getMessage());
            $execution->setFinishedAt(new DateTimeImmutable());
            $this->nodeExecutions->save($execution);

            $this->eventRecorder->record(
                workflowRunId: $workflowRunId,
                type: ExecutionEventType::NodeFailed,
                payload: ['error' => $exception->getMessage()],
                nodeId: $nodeId,
                nodeExecutionId: $execution->getId(),
            );

            throw $exception;
        }

        return $execution;
    }
}
