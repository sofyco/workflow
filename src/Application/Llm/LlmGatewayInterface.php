<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\Llm;

interface LlmGatewayInterface
{
    public function complete(LlmRequest $request): LlmResponse;
}
