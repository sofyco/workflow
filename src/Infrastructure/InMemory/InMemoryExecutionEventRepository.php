<?php declare(strict_types=1);

namespace Sofyco\Workflow\Infrastructure\InMemory;

use DateTimeImmutable;
use Sofyco\Workflow\Application\Id\IdGeneratorInterface;
use Sofyco\Workflow\Domain\Model\ExecutionEvent;
use Sofyco\Workflow\Domain\Enum\ExecutionEventType;
use Sofyco\Workflow\Infrastructure\Doctrine\ExecutionEventRepositoryInterface;

final class InMemoryExecutionEventRepository implements ExecutionEventRepositoryInterface
{
    /** @var array<string, ExecutionEvent> */
    private array $events = [];

    public function __construct(
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    /**
     * @return list<ExecutionEvent>
     */
    public function findByWorkflowRunId(string $workflowRunId): array
    {
        $events = array_values(array_filter(
            $this->events,
            static fn (ExecutionEvent $event): bool => $event->getWorkflowRunId() === $workflowRunId,
        ));

        usort(
            $events,
            static fn (ExecutionEvent $a, ExecutionEvent $b): int => $a->getCreatedAt() <=> $b->getCreatedAt(),
        );

        return $events;
    }

    public function save(ExecutionEvent $event): void
    {
        $this->events[$event->getId()] = $event;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(
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
        $this->save($event);

        return $event;
    }
}
