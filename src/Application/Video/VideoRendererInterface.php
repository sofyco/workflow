<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Video;

interface VideoRendererInterface
{
    /**
     * @param array<string, mixed> $settings
     */
    public function render(
        string $audioPath,
        string $subtitlesPath,
        ?string $backgroundPath,
        array $settings,
    ): VideoRenderResult;
}
