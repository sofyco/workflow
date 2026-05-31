<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\NodeRunner;

final readonly class NodeExecutionResult
{
    /**
     * @param list<\Sofyco\Workflow\Domain\Model\Artifact> $outputArtifacts
     * @param array<string, mixed> $outputData
     */
    public function __construct(
        public array $outputArtifacts = [],
        public array $outputData = [],
    )
    {
    }
}
