<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\NodeRunner;

use RuntimeException;
use Sofyco\Workflow\Application\Artifact\ArtifactStorageInterface;
use Sofyco\Workflow\Application\Llm\LlmGatewayInterface;
use Sofyco\Workflow\Application\Llm\LlmRequest;
use Sofyco\Workflow\Application\Llm\PromptRenderer;
use Sofyco\Workflow\Application\Support\SettingsAccessor;
use Sofyco\Workflow\Domain\Enum\ArtifactType;
use Sofyco\Workflow\Domain\Enum\NodeType;

final readonly class PromptNodeRunner implements NodeRunnerInterface
{
    public function __construct(
        private ArtifactStorageInterface $artifactStorage,
        private PromptRenderer $promptRenderer,
        private LlmGatewayInterface $llmGateway,
    ) {
    }

    public function supports(NodeType $type): bool
    {
        return $type === NodeType::Prompt;
    }

    public function run(NodeExecutionContext $context): NodeExecutionResult
    {
        $settings = $context->node->getSettings();
        $template = SettingsAccessor::string($settings, 'userPromptTemplate', '{{ input.source_description.content }}');
        $systemPrompt = SettingsAccessor::string($settings, 'systemPrompt', 'You are a helpful assistant.');
        $responseMimeType = SettingsAccessor::string($settings, 'responseMimeType', 'text/plain');
        $outputAlias = SettingsAccessor::string($settings, 'outputAlias', $this->resolveDefaultOutputAlias($context));

        $userPrompt = $this->promptRenderer->render($template, $context->variables);
        $response = $this->llmGateway->complete(new LlmRequest(
            provider: SettingsAccessor::string($settings, 'provider', 'openai'),
            model: SettingsAccessor::string($settings, 'model', 'gpt-4.1-mini'),
            messages: [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            responseMimeType: $responseMimeType,
            jsonSchema: SettingsAccessor::arrayOrNull($settings, 'jsonSchema'),
            temperature: SettingsAccessor::float($settings, 'temperature', 0.7),
        ));

        $extension = $responseMimeType === 'application/json' ? 'json' : 'txt';
        $artifact = $this->artifactStorage->storeContent(
            run: $context->run,
            execution: $context->execution,
            alias: $outputAlias,
            content: $response->rawContent,
            type: ArtifactType::File,
            mimeType: $responseMimeType,
            extension: $extension,
            metadata: [
                'model' => $settings['model'] ?? null,
                'provider' => $settings['provider'] ?? null,
            ],
        );

        return new NodeExecutionResult(outputArtifacts: [$artifact]);
    }

    private function resolveDefaultOutputAlias(NodeExecutionContext $context): string
    {
        $outputPorts = $context->node->getOutputPorts();

        if ($outputPorts === []) {
            throw new RuntimeException('Prompt node must define at least one output port.');
        }

        return $outputPorts[0]->name;
    }
}
