<?php declare(strict_types=1);

namespace Sofyco\Workflow\Domain\Model;

use DateTimeImmutable;

final class LlmCall
{
    /** @var list<array<string, mixed>> */
    private array $messages = [];

    /** @var array<string, mixed>|null */
    private ?array $jsonSchema = null;

    /** @var array<string, mixed>|null */
    private ?array $parsedResponse = null;

    public function __construct(
        private string            $id,
        private string            $workflowRunId,
        private string            $nodeExecutionId,
        private string            $provider,
        private string            $model,
        private string            $rawResponse,
        private DateTimeImmutable $createdAt,
        private int               $inputTokens = 0,
        private int               $outputTokens = 0,
        private ?float            $cost = null,
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getWorkflowRunId(): string
    {
        return $this->workflowRunId;
    }

    public function getNodeExecutionId(): string
    {
        return $this->nodeExecutionId;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @param list<array<string, mixed>> $messages
     */
    public function setMessages(array $messages): void
    {
        $this->messages = $messages;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getJsonSchema(): ?array
    {
        return $this->jsonSchema;
    }

    /**
     * @param array<string, mixed>|null $jsonSchema
     */
    public function setJsonSchema(?array $jsonSchema): void
    {
        $this->jsonSchema = $jsonSchema;
    }

    public function getRawResponse(): string
    {
        return $this->rawResponse;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getParsedResponse(): ?array
    {
        return $this->parsedResponse;
    }

    /**
     * @param array<string, mixed>|null $parsedResponse
     */
    public function setParsedResponse(?array $parsedResponse): void
    {
        $this->parsedResponse = $parsedResponse;
    }

    public function getInputTokens(): int
    {
        return $this->inputTokens;
    }

    public function getOutputTokens(): int
    {
        return $this->outputTokens;
    }

    public function getCost(): ?float
    {
        return $this->cost;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
