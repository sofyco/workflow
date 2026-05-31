<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\NodeRunner;

use RuntimeException;
use Sofyco\Workflow\Domain\Enum\NodeType;

final readonly class InputNodeRunner implements NodeRunnerInterface
{
    public function supports(NodeType $type): bool
    {
        return $type === NodeType::Input;
    }

    public function run(NodeExecutionContext $context): NodeExecutionResult
    {
        $outputArtifacts = [];

        foreach ($context->node->getOutputPorts() as $port) {
            foreach ($context->inputArtifacts[$port->name] ?? [] as $artifact) {
                $outputArtifacts[] = $artifact;
            }
        }

        if ($outputArtifacts === []) {
            throw new RuntimeException('Input node has no artifacts to expose.');
        }

        return new NodeExecutionResult(outputArtifacts: $outputArtifacts);
    }
}
