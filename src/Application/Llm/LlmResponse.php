<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Llm;

final readonly class LlmResponse
{
    /**
     * @param array<string, mixed>|null $parsedContent
     */
    public function __construct(
        public string $rawContent,
        public ?array $parsedContent = null,
        public int    $inputTokens = 0,
        public int    $outputTokens = 0,
        public ?float $cost = null,
    )
    {
    }
}
