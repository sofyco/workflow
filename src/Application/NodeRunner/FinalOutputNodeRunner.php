<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\NodeRunner;

use Sofyco\Workflow\Application\Artifact\ArtifactStorageInterface;
use Sofyco\Workflow\Domain\Enum\ArtifactType;
use Sofyco\Workflow\Domain\Enum\NodeType;

final readonly class FinalOutputNodeRunner implements NodeRunnerInterface
{
    public function __construct(
        private ArtifactStorageInterface $artifactStorage,
    ) {
    }

    public function supports(NodeType $type): bool
    {
        return $type === NodeType::FinalOutput;
    }

    public function run(NodeExecutionContext $context): NodeExecutionResult
    {
        $manifest = [];

        foreach ($context->node->getInputPorts() as $port) {
            foreach ($context->inputArtifacts[$port->name] ?? [] as $artifact) {
                $manifest[$port->name] = [
                    'artifactId' => $artifact->getId(),
                    'type' => $artifact->getType()->value,
                    'mimeType' => $artifact->getMimeType(),
                    'storagePath' => $artifact->getStoragePath(),
                    'metadata' => $artifact->getMetadata(),
                ];
            }
        }

        $artifact = $this->artifactStorage->storeContent(
            run: $context->run,
            execution: $context->execution,
            alias: 'result',
            content: json_encode($manifest, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            type: ArtifactType::File,
            mimeType: 'application/json',
            extension: 'json',
            metadata: ['schema' => 'workflow_final_output_v1'],
        );

        return new NodeExecutionResult(
            outputArtifacts: [$artifact],
            outputData: $manifest,
        );
    }
}
