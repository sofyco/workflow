<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Tts;

use RuntimeException;
use Sofyco\Workflow\Application\Support\SettingsAccessor;

final readonly class TtsGatewayRegistry
{
    /**
     * @param iterable<TtsGatewayInterface> $gateways
     */
    public function __construct(
        private iterable $gateways,
    ) {
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function synthesize(string $text, array $settings): TtsSynthesisResult
    {
        $provider = SettingsAccessor::string($settings, 'provider');

        foreach ($this->gateways as $gateway) {
            if ($gateway->supports($provider)) {
                return $gateway->synthesize($text, $settings);
            }
        }

        throw new RuntimeException(sprintf('TTS provider "%s" not found.', $provider));
    }
}
