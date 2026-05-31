<?php declare(strict_types=1);

namespace Sofyco\Workflow\Tests\Support;

use Sofyco\Workflow\Application\Image\ImageGenerationResult;
use Sofyco\Workflow\Application\Image\ImageGeneratorInterface;

final class FakeImageGenerator implements ImageGeneratorInterface
{
    /** @var list<array{prompt: string, settings: array<string, mixed>}> */
    public array $calls = [];

    public function supports(string $provider): bool
    {
        return in_array($provider, ['openai', 'dalle'], true);
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function generate(string $prompt, array $settings): ImageGenerationResult
    {
        $this->calls[] = ['prompt' => $prompt, 'settings' => $settings];

        $path = tempnam(sys_get_temp_dir(), 'generated_image_') . '.png';
        $payload = sprintf(
            "FAKE-PNG\nprompt:%s\nmodel:%s\nsize:%s\nhash:%s\n",
            $prompt,
            is_string($settings['model'] ?? null) ? $settings['model'] : 'gpt-image-1',
            is_string($settings['size'] ?? null) ? $settings['size'] : '1024x1024',
            hash('sha256', $prompt),
        );
        file_put_contents($path, $payload);

        return new ImageGenerationResult(
            localPath: $path,
            mimeType: 'image/png',
            extension: 'png',
            metadata: [
                'provider' => $settings['provider'] ?? 'openai',
                'model' => $settings['model'] ?? 'gpt-image-1',
                'width' => 1024,
                'height' => 1024,
            ],
        );
    }
}
