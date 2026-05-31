<?php declare(strict_types=1);

namespace Sofyco\Workflow\Infrastructure\InMemory;

use Sofyco\Workflow\Domain\Model\WorkflowVersion;
use Sofyco\Workflow\Infrastructure\Doctrine\WorkflowVersionRepositoryInterface;

final class InMemoryWorkflowVersionRepository implements WorkflowVersionRepositoryInterface
{
    /** @var array<string, WorkflowVersion> */
    private array $versions = [];

    public function find(string $id): ?WorkflowVersion
    {
        return $this->versions[$id] ?? null;
    }

    public function findLatestByWorkflowId(string $workflowId): ?WorkflowVersion
    {
        $latest = null;

        foreach ($this->versions as $version) {
            if ($version->getWorkflowId() !== $workflowId) {
                continue;
            }

            if ($latest === null || $version->getVersion() > $latest->getVersion()) {
                $latest = $version;
            }
        }

        return $latest;
    }

    public function save(WorkflowVersion $version): void
    {
        $this->versions[$version->getId()] = $version;
    }
}
