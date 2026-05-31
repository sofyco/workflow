<?php declare(strict_types=1);

namespace Sofyco\Workflow\Tests\Support;

use RuntimeException;
use Sofyco\Workflow\Domain\Model\Artifact;

final class WorkflowArtifactHelper
{
    /**
     * @return array{title: string, url: string, publishedAt: string, excerpt: string}
     */
    public static function extractFirstPostFromParsingRun(
        WorkflowTestHarness $harness,
        string $workflowRunId,
    ): array {
        $postsArtifact = self::findNodeOutputArtifact($harness, $workflowRunId, 'parse_posts', 'posts');
        $payload = json_decode($harness->artifactStorage->readContent($postsArtifact), true, flags: JSON_THROW_ON_ERROR);

        if (!is_array($payload) || !isset($payload['posts']) || !is_array($payload['posts']) || !isset($payload['posts'][0]) || !is_array($payload['posts'][0])) {
            throw new RuntimeException('Parsing workflow did not produce any posts.');
        }

        /** @var array{title: string, url: string, publishedAt: string, excerpt: string} $post */
        $post = $payload['posts'][0];

        return $post;
    }

    public static function findNodeOutputArtifact(
        WorkflowTestHarness $harness,
        string $workflowRunId,
        string $nodeId,
        string $alias,
    ): Artifact {
        $executions = $harness->nodeExecutions->findByWorkflowRunIdAndNodeId($workflowRunId, $nodeId);

        foreach ($executions as $execution) {
            foreach ($execution->getOutputArtifacts() as $reference) {
                if ($reference->alias !== $alias) {
                    continue;
                }

                $artifact = $harness->artifacts->find($reference->artifactId);

                if ($artifact !== null) {
                    return $artifact;
                }
            }
        }

        throw new RuntimeException(sprintf('Artifact "%s" from node "%s" not found.', $alias, $nodeId));
    }
}
