<?php declare(strict_types=1);

namespace Sofyco\Workflow\Infrastructure\Doctrine;

use LogicException;
use Sofyco\Workflow\Domain\Model\Artifact;

final class ArtifactRepository implements ArtifactRepositoryInterface
{
    public function find(string $id): ?Artifact
    {
        throw new LogicException('Not implemented.');
    }

    /**
     * @return list<Artifact>
     */
    public function findByWorkflowRunId(string $workflowRunId): array
    {
        throw new LogicException('Not implemented.');
    }

    public function save(Artifact $artifact): void
    {
        throw new LogicException('Not implemented.');
    }
}
