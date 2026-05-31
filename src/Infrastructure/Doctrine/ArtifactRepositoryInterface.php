<?php declare(strict_types=1);

namespace Sofyco\Workflow\Infrastructure\Doctrine;

use Sofyco\Workflow\Domain\Model\Artifact;

interface ArtifactRepositoryInterface
{
    public function find(string $id): ?Artifact;

    /**
     * @return list<Artifact>
     */
    public function findByWorkflowRunId(string $workflowRunId): array;

    public function save(Artifact $artifact): void;
}
