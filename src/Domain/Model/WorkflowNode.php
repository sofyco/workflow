<?php declare(strict_types=1);

namespace Sofyco\Workflow\Domain\Model;

use Sofyco\Workflow\Domain\Enum\NodeType;

final class WorkflowNode
{
    /** @var array<string, WorkflowPort> */
    private array $inputPorts = [];

    /** @var array<string, WorkflowPort> */
    private array $outputPorts = [];

    /** @var array<string, mixed> */
    private array $settings = [];

    public function __construct(
        private string       $id,
        private NodeType     $type,
        private string       $name,
        private ?RetryPolicy $retryPolicy = null,
        private ?Position    $position = null,
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): NodeType
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return list<WorkflowPort>
     */
    public function getInputPorts(): array
    {
        return array_values($this->inputPorts);
    }

    public function getInputPort(string $name): ?WorkflowPort
    {
        return $this->inputPorts[$name] ?? null;
    }

    public function addInputPort(WorkflowPort $port): void
    {
        $this->inputPorts[$port->name] = $port;
    }

    /**
     * @return list<WorkflowPort>
     */
    public function getOutputPorts(): array
    {
        return array_values($this->outputPorts);
    }

    public function getOutputPort(string $name): ?WorkflowPort
    {
        return $this->outputPorts[$name] ?? null;
    }

    public function addOutputPort(WorkflowPort $port): void
    {
        $this->outputPorts[$port->name] = $port;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    public function getRetryPolicy(): ?RetryPolicy
    {
        return $this->retryPolicy;
    }

    public function setRetryPolicy(?RetryPolicy $retryPolicy): void
    {
        $this->retryPolicy = $retryPolicy;
    }

    public function getPosition(): ?Position
    {
        return $this->position;
    }

    public function setPosition(?Position $position): void
    {
        $this->position = $position;
    }
}
