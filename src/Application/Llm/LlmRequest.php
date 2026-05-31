<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Llm;

final readonly class LlmRequest
{
    /**
     * @param list<array<string, mixed>> $messages
     * @param array<string, mixed>|null $jsonSchema
     */
    public function __construct(
        public string  $provider,
        public string  $model,
        public array   $messages,
        public ?string $responseMimeType = null,
        public ?array  $jsonSchema = null,
        public float   $temperature = 0.7,
    )
    {
    }
}
