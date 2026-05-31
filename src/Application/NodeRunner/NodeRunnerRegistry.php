<?php declare(strict_types=1);

namespace Sofyco\Workflow\Application\NodeRunner;

use RuntimeException;
use Sofyco\Workflow\Domain\Enum\NodeType;

final readonly class NodeRunnerRegistry
{
    /**
     * @param iterable<NodeRunnerInterface> $runners
     */
    public function __construct(
        private iterable $runners,
    )
    {
    }

    public function get(NodeType $type): NodeRunnerInterface
    {
        foreach ($this->runners as $runner) {
            if ($runner->supports($type)) {
                return $runner;
            }
        }

        throw new RuntimeException(sprintf('Node runner for type "%s" not found.', $type->value));
    }
}
