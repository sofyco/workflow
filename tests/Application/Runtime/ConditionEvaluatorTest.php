<?php declare(strict_types=1);

namespace Sofyco\Workflow\Tests\Application\Runtime;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sofyco\Workflow\Application\Runtime\ConditionEvaluator;
use Sofyco\Workflow\Application\Runtime\FieldAccessor;
use Sofyco\Workflow\Domain\Model\ConditionDefinition;
use Sofyco\Workflow\Domain\Model\WorkflowRun;
use Sofyco\Workflow\Domain\Enum\ConditionOperator;
use Sofyco\Workflow\Domain\Enum\WorkflowRunStatus;
use DateTimeImmutable;

final class ConditionEvaluatorTest extends TestCase
{
    #[Test]
    public function itEvaluatesNestedFieldConditions(): void
    {
        $evaluator = new ConditionEvaluator(new FieldAccessor());
        $run = new WorkflowRun(
            id: 'run_1',
            workflowId: 'wf_1',
            workflowVersionId: 'wfv_1',
            workflowVersion: 1,
            status: WorkflowRunStatus::Running,
            createdAt: new DateTimeImmutable(),
        );

        $context = [
            'nodes' => [
                'validate_story' => [
                    'latest' => [
                        'output' => [
                            'validation_result' => [
                                'is_valid' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $condition = new ConditionDefinition(
            field: 'nodes.validate_story.latest.output.validation_result.is_valid',
            operator: ConditionOperator::Equals,
            value: true,
        );

        self::assertTrue($evaluator->matches($condition, $run, $context));
    }
}
