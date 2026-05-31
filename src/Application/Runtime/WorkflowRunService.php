<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Runtime;

use DateTimeImmutable;
use RuntimeException;
use Sofyco\Workflow\Application\Artifact\ArtifactStorageInterface;
use Sofyco\Workflow\Application\Id\IdGeneratorInterface;
use Sofyco\Workflow\Domain\Model\ArtifactReference;
use Sofyco\Workflow\Domain\Model\WorkflowRun;
use Sofyco\Workflow\Domain\Enum\ArtifactType;
use Sofyco\Workflow\Domain\Enum\ExecutionEventType;
use Sofyco\Workflow\Domain\Enum\WorkflowRunStatus;
use Sofyco\Workflow\Infrastructure\Doctrine\ArtifactRepositoryInterface;
use Sofyco\Workflow\Infrastructure\Doctrine\WorkflowRunRepositoryInterface;
use Sofyco\Workflow\Infrastructure\Doctrine\WorkflowVersionRepositoryInterface;

final readonly class WorkflowRunService
{
    public function __construct(
        private WorkflowVersionRepositoryInterface $versions,
        private WorkflowRunRepositoryInterface $runs,
        private ArtifactRepositoryInterface $artifacts,
        private ArtifactStorageInterface $artifactStorage,
        private WorkflowScheduler $scheduler,
        private ExecutionEventRecorder $eventRecorder,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    /**
     * @param array<string, array{type: string, mimeType: string, extension: string, content: string}> $inputArtifacts
     */
    public function start(string $workflowId, string $userId, array $inputArtifacts): WorkflowRun
    {
        $version = $this->versions->findLatestByWorkflowId($workflowId);

        if ($version === null) {
            throw new RuntimeException(sprintf('No published version found for workflow "%s".', $workflowId));
        }

        $run = new WorkflowRun(
            id: $this->idGenerator->generate(),
            workflowId: $workflowId,
            workflowVersionId: $version->getId(),
            workflowVersion: $version->getVersion(),
            status: WorkflowRunStatus::Running,
            createdAt: new DateTimeImmutable(),
            startedAt: new DateTimeImmutable(),
        );

        $references = [];

        foreach ($inputArtifacts as $alias => $input) {
            $artifact = $this->artifactStorage->storeContent(
                run: $run,
                execution: null,
                alias: $alias,
                content: $input['content'],
                type: ArtifactType::from($input['type']),
                mimeType: $input['mimeType'],
                extension: $input['extension'],
            );
            $this->artifacts->save($artifact);

            $references[] = new ArtifactReference(
                artifactId: $artifact->getId(),
                alias: $alias,
                type: ArtifactType::from($input['type']),
                mimeType: $input['mimeType'],
            );
        }

        $run->setInputArtifacts($references);
        $this->runs->save($run);

        $this->eventRecorder->record(
            workflowRunId: $run->getId(),
            type: ExecutionEventType::WorkflowStarted,
        );

        return $run;
    }

    public function process(string $workflowRunId): void
    {
        while ($this->scheduler->scheduleReadyNodes($workflowRunId) > 0) {
        }

        $run = $this->runs->find($workflowRunId);

        if ($run !== null && $run->getStatus() === WorkflowRunStatus::Completed) {
            $this->eventRecorder->record(
                workflowRunId: $workflowRunId,
                type: ExecutionEventType::WorkflowCompleted,
            );
        }
    }
}
