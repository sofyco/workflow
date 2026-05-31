<?php declare(strict_types=1);

namespace Sofyco\Workflow\Tests\Support;

use Sofyco\Workflow\Application\Video\VideoRendererInterface;
use Sofyco\Workflow\Application\Video\VideoRenderResult;

final class FakeVideoRenderer implements VideoRendererInterface
{
    /** @var list<array{audioPath: string, subtitlesPath: string, backgroundPath: ?string, settings: array<string, mixed>, subtitlesContent: string}> */
    public array $calls = [];

    /**
     * @param array<string, mixed> $settings
     */
    public function render(
        string $audioPath,
        string $subtitlesPath,
        ?string $backgroundPath,
        array $settings,
    ): VideoRenderResult {
        $path = tempnam(sys_get_temp_dir(), 'rendered_video_') . '.mp4';
        $subtitles = file_get_contents($subtitlesPath) ?: '';
        $audio = file_get_contents($audioPath) ?: '';

        $this->calls[] = [
            'audioPath' => $audioPath,
            'subtitlesPath' => $subtitlesPath,
            'backgroundPath' => $backgroundPath,
            'settings' => $settings,
            'subtitlesContent' => $subtitles,
        ];

        $manifest = [
            'format' => 'mp4',
            'resolution' => $settings['resolution'] ?? '720x1280',
            'fps' => $settings['fps'] ?? 30,
            'subtitlesOverlay' => true,
            'subtitlesHash' => hash('sha256', $subtitles),
            'audioHash' => hash('sha256', $audio),
            'background' => $backgroundPath !== null ? basename($backgroundPath) : null,
        ];

        file_put_contents($path, 'FAKE-MP4-' . json_encode($manifest, JSON_THROW_ON_ERROR));

        return new VideoRenderResult(
            localPath: $path,
            mimeType: 'video/mp4',
            extension: 'mp4',
            metadata: [
                'duration' => 42.0,
                'width' => 720,
                'height' => 1280,
                'fps' => $settings['fps'] ?? 30,
                'subtitlesOverlay' => true,
            ],
        );
    }
}
