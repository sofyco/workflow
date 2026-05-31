<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\NodeRunner;

use RuntimeException;
use Sofyco\Workflow\Application\Artifact\ArtifactContentReader;
use Sofyco\Workflow\Application\Artifact\ArtifactStorageInterface;
use Sofyco\Workflow\Application\Support\SettingsAccessor;
use Sofyco\Workflow\Application\Tts\TtsGatewayRegistry;
use Sofyco\Workflow\Domain\Enum\ArtifactType;
use Sofyco\Workflow\Domain\Enum\NodeType;

final readonly class TextToSpeechNodeRunner implements NodeRunnerInterface
{
    public function __construct(
        private ArtifactContentReader $artifactContentReader,
        private ArtifactStorageInterface $artifactStorage,
        private TtsGatewayRegistry $ttsGatewayRegistry,
    ) {
    }

    public function supports(NodeType $type): bool
    {
        return $type === NodeType::TextToSpeech;
    }

    public function run(NodeExecutionContext $context): NodeExecutionResult
    {
        $settings = $context->node->getSettings();
        $scriptArtifact = $this->resolveInputArtifact($context, SettingsAccessor::string($settings, 'inputPort', 'script'));
        $script = $this->artifactContentReader->readText($scriptArtifact);

        $synthesis = $this->ttsGatewayRegistry->synthesize($script, $settings);

        $artifact = $this->artifactStorage->storeFile(
            run: $context->run,
            execution: $context->execution,
            alias: SettingsAccessor::string($settings, 'outputAlias', 'audio'),
            localPath: $synthesis->localPath,
            type: ArtifactType::Audio,
            mimeType: $synthesis->mimeType,
            extension: $synthesis->extension,
            metadata: array_merge($synthesis->metadata, [
                'provider' => $settings['provider'] ?? null,
                'voice' => $settings['voice'] ?? null,
            ]),
        );

        if (is_file($synthesis->localPath)) {
            unlink($synthesis->localPath);
        }

        return new NodeExecutionResult(outputArtifacts: [$artifact]);
    }

    private function resolveInputArtifact(NodeExecutionContext $context, string $portName): \Sofyco\Workflow\Domain\Model\Artifact
    {
        $artifacts = $context->inputArtifacts[$portName] ?? [];

        if ($artifacts === []) {
            throw new RuntimeException(sprintf('Missing input artifact for port "%s".', $portName));
        }

        return $artifacts[0];
    }
}
