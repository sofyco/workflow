<?php declare(strict_types=1);

namespace Sofyco\Workflow\Domain\Enum;

enum ExecutionEventType: string
{
    case WorkflowStarted = 'workflow_started';
    case NodeScheduled = 'node_scheduled';
    case NodeStarted = 'node_started';
    case ArtifactCreated = 'artifact_created';
    case NodeCompleted = 'node_completed';
    case NodeFailed = 'node_failed';
    case NodeRetried = 'node_retried';
    case WorkflowCompleted = 'workflow_completed';
    case WorkflowFailed = 'workflow_failed';
}
