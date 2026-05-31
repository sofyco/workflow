<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Runtime;

use DateTimeImmutable;
use Sofyco\Workflow\Application\Id\IdGeneratorInterface;
use Sofyco\Workflow\Domain\Model\ExecutionEvent;
use Sofyco\Workflow\Domain\Enum\ExecutionEventType;
use Sofyco\Workflow\Infrastructure\Doctrine\ExecutionEventRepositoryInterface;

final readonly class ExecutionEventRecorder
{
    public function __construct(
        private ExecutionEventRepositoryInterface $events,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function record(
        string $workflowRunId,
        ExecutionEventType $type,
        array $payload = [],
        ?string $nodeId = null,
        ?string $nodeExecutionId = null,
    ): ExecutionEvent {
        $event = new ExecutionEvent(
            id: $this->idGenerator->generate(),
            workflowRunId: $workflowRunId,
            type: $type,
            createdAt: new DateTimeImmutable(),
            nodeId: $nodeId,
            nodeExecutionId: $nodeExecutionId,
        );
        $event->setPayload($payload);
        $this->events->save($event);

        return $event;
    }
}
