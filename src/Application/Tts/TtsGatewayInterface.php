<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Tts;

interface TtsGatewayInterface
{
    public function supports(string $provider): bool;

    /**
     * @param array<string, mixed> $settings
     */
    public function synthesize(string $text, array $settings): TtsSynthesisResult;
}
