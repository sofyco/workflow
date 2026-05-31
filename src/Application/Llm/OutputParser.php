<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Llm;

use LogicException;

final readonly class OutputParser
{
    /**
     * @return array<string, mixed>|string
     */
    public function parse(string $content, ?string $mimeType = null): array|string
    {
        throw new LogicException('Not implemented.');
    }
}
