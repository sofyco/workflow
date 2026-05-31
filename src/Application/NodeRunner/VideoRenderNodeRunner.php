<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\NodeRunner;

use RuntimeException;
use Sofyco\Workflow\Application\Artifact\ArtifactStorageInterface;
use Sofyco\Workflow\Application\Support\SettingsAccessor;
use Sofyco\Workflow\Application\Video\VideoRendererInterface;
use Sofyco\Workflow\Domain\Enum\ArtifactType;
use Sofyco\Workflow\Domain\Enum\NodeType;

final readonly class VideoRenderNodeRunner implements NodeRunnerInterface
{
    public function __construct(
        private ArtifactStorageInterface $artifactStorage,
        private VideoRendererInterface $videoRenderer,
    ) {
    }

    public function supports(NodeType $type): bool
    {
        return $type === NodeType::VideoRender;
    }

    public function run(NodeExecutionContext $context): NodeExecutionResult
    {
        $settings = $context->node->getSettings();
        $audioArtifact = $this->resolveInputArtifact($context, 'audio');
        $subtitlesArtifact = $this->resolveInputArtifact($context, 'subtitles');

        $backgroundPath = null;
        $backgroundArtifacts = $context->inputArtifacts['background'] ?? [];

        if ($backgroundArtifacts !== []) {
            $backgroundPath = $this->artifactStorage->createTemporaryLocalFile($backgroundArtifacts[0]);
        }

        $audioPath = $this->artifactStorage->createTemporaryLocalFile($audioArtifact);
        $subtitlesPath = $this->artifactStorage->createTemporaryLocalFile($subtitlesArtifact);
        $rendered = null;

        try {
            $rendered = $this->videoRenderer->render(
                audioPath: $audioPath,
                subtitlesPath: $subtitlesPath,
                backgroundPath: $backgroundPath,
                settings: $settings,
            );

            $artifact = $this->artifactStorage->storeFile(
                run: $context->run,
                execution: $context->execution,
                alias: SettingsAccessor::string($settings, 'outputAlias', 'video'),
                localPath: $rendered->localPath,
                type: ArtifactType::Video,
                mimeType: $rendered->mimeType,
                extension: $rendered->extension,
                metadata: array_merge($rendered->metadata, [
                    'subtitlesOverlay' => true,
                    'resolution' => $settings['resolution'] ?? null,
                ]),
            );
        } finally {
            foreach ([$audioPath, $subtitlesPath, $backgroundPath, $rendered?->localPath] as $path) {
                if ($path !== null && is_file($path)) {
                    unlink($path);
                }
            }
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
