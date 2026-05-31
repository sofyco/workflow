<?php declare(strict_types=1);

namespace Sofyco\Workflow\Infrastructure\InMemory;

use Sofyco\Workflow\Domain\Model\Artifact;
use Sofyco\Workflow\Infrastructure\Doctrine\ArtifactRepositoryInterface;

final class InMemoryArtifactRepository implements ArtifactRepositoryInterface
{
    /** @var array<string, Artifact> */
    private array $artifacts = [];

    public function find(string $id): ?Artifact
    {
        return $this->artifacts[$id] ?? null;
    }

    /**
     * @return list<Artifact>
     */
    public function findByWorkflowRunId(string $workflowRunId): array
    {
        return array_values(array_filter(
            $this->artifacts,
            static fn (Artifact $artifact): bool => $artifact->getWorkflowRunId() === $workflowRunId,
        ));
    }

    public function save(Artifact $artifact): void
    {
        $this->artifacts[$artifact->getId()] = $artifact;
    }
}
