<?php declare(strict_types=1);

namespace Sofyco\Workflow\Tests\Support;

use Sofyco\Workflow\Application\Tts\TtsGatewayInterface;
use Sofyco\Workflow\Application\Tts\TtsSynthesisResult;

final class FakeElevenLabsTtsGateway implements TtsGatewayInterface
{
    /** @var list<array{text: string, settings: array<string, mixed>}> */
    public array $calls = [];

    public function supports(string $provider): bool
    {
        return $provider === 'elevenlabs';
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function synthesize(string $text, array $settings): TtsSynthesisResult
    {
        $this->calls[] = ['text' => $text, 'settings' => $settings];

        $path = tempnam(sys_get_temp_dir(), 'elevenlabs_audio_') . '.mp3';
        $voice = is_string($settings['voice'] ?? null) ? $settings['voice'] : 'unknown';
        $model = is_string($settings['model'] ?? null) ? $settings['model'] : 'eleven_multilingual_v2';

        $payload = sprintf(
            "FAKE-ELEVENLABS-AUDIO\nvoice:%s\nmodel:%s\nchars:%d\n%s\n",
            $voice,
            $model,
            strlen($text),
            hash('sha256', $text),
        );
        file_put_contents($path, $payload);

        return new TtsSynthesisResult(
            localPath: $path,
            mimeType: 'audio/mpeg',
            extension: 'mp3',
            metadata: [
                'provider' => 'elevenlabs',
                'voice' => $settings['voice'] ?? null,
                'model' => $settings['model'] ?? null,
                'duration' => round(strlen($text) / 15, 1),
            ],
        );
    }
}
