<?php declare(strict_types=1);

namespace Sofyco\Workflow\Tests\Application\NodeRunner;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Sofyco\Workflow\Application\NodeRunner\InputNodeRunner;
use Sofyco\Workflow\Application\NodeRunner\NodeRunnerRegistry;
use Sofyco\Workflow\Domain\Enum\NodeType;

final class NodeRunnerRegistryTest extends TestCase
{
    #[Test]
    public function itResolvesRegisteredRunner(): void
    {
        $registry = new NodeRunnerRegistry([
            new InputNodeRunner(),
        ]);

        self::assertInstanceOf(InputNodeRunner::class, $registry->get(NodeType::Input));
    }

    #[Test]
    public function itThrowsWhenRunnerIsMissing(): void
    {
        $registry = new NodeRunnerRegistry([]);

        $this->expectException(RuntimeException::class);
        $registry->get(NodeType::Prompt);
    }
}
