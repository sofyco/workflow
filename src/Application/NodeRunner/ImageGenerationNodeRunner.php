<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\NodeRunner;

use RuntimeException;
use Sofyco\Workflow\Application\Artifact\ArtifactContentReader;
use Sofyco\Workflow\Application\Artifact\ArtifactStorageInterface;
use Sofyco\Workflow\Application\Image\ImageGeneratorRegistry;
use Sofyco\Workflow\Application\Support\SettingsAccessor;
use Sofyco\Workflow\Domain\Enum\ArtifactType;
use Sofyco\Workflow\Domain\Enum\NodeType;
use Sofyco\Workflow\Domain\Model\Artifact;

final readonly class ImageGenerationNodeRunner implements NodeRunnerInterface
{
    public function __construct(
        private ArtifactContentReader $artifactContentReader,
        private ArtifactStorageInterface $artifactStorage,
        private ImageGeneratorRegistry $imageGeneratorRegistry,
    ) {
    }

    public function supports(NodeType $type): bool
    {
        return $type === NodeType::ImageGeneration;
    }

    public function run(NodeExecutionContext $context): NodeExecutionResult
    {
        $settings = $context->node->getSettings();
        $inputPort = SettingsAccessor::string($settings, 'inputPort', 'prompt');
        $artifact = $this->resolveInputArtifact($context, $inputPort);
        $prompt = $this->resolvePrompt($artifact, $settings);

        $generated = $this->imageGeneratorRegistry->generate($prompt, $settings);

        $outputArtifact = $this->artifactStorage->storeFile(
            run: $context->run,
            execution: $context->execution,
            alias: SettingsAccessor::string($settings, 'outputAlias', 'image'),
            localPath: $generated->localPath,
            type: ArtifactType::Image,
            mimeType: $generated->mimeType,
            extension: $generated->extension,
            metadata: array_merge($generated->metadata, [
                'provider' => $settings['provider'] ?? null,
                'model' => $settings['model'] ?? null,
                'prompt' => $prompt,
            ]),
        );

        if (is_file($generated->localPath)) {
            unlink($generated->localPath);
        }

        return new NodeExecutionResult(outputArtifacts: [$outputArtifact]);
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function resolvePrompt(Artifact $artifact, array $settings): string
    {
        if ($artifact->getMimeType() === 'application/json') {
            $payload = $this->artifactContentReader->readJson($artifact);
            $promptField = SettingsAccessor::string($settings, 'promptField', 'excerpt');

            if (!array_key_exists($promptField, $payload) || !is_scalar($payload[$promptField])) {
                throw new RuntimeException(sprintf('JSON artifact has no scalar field "%s" for image prompt.', $promptField));
            }

            return (string) $payload[$promptField];
        }

        return $this->artifactContentReader->readText($artifact);
    }

    private function resolveInputArtifact(NodeExecutionContext $context, string $portName): Artifact
    {
        $artifacts = $context->inputArtifacts[$portName] ?? [];

        if ($artifacts === []) {
            throw new RuntimeException(sprintf('Missing input artifact for port "%s".', $portName));
        }

        return $artifacts[0];
    }
}
