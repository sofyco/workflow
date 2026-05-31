<?php declare(strict_types=1);

namespace Sofyco\Workflow\Tests\Domain\Model;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sofyco\Workflow\Domain\Model\NodeExecution;
use Sofyco\Workflow\Domain\Enum\NodeExecutionStatus;

final class NodeExecutionTest extends TestCase
{
    #[Test]
    public function itBuildsExecutionKey(): void
    {
        self::assertSame(
            'run_123:rewrite_story:2',
            NodeExecution::buildExecutionKey('run_123', 'rewrite_story', 2),
        );
    }

    #[Test]
    public function itReportsCompletedStatus(): void
    {
        $execution = new NodeExecution(
            id: 'exec_1',
            workflowRunId: 'run_123',
            nodeId: 'rewrite_story',
            attempt: 1,
            executionKey: 'run_123:rewrite_story:1',
            status: NodeExecutionStatus::Completed,
            createdAt: new DateTimeImmutable(),
        );

        self::assertTrue($execution->isCompleted());
    }
}
