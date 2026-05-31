<?php declare(strict_types=1);

namespace Sofyco\Workflow\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sofyco\Workflow\Domain\Enum\NodeExecutionStatus;
use Sofyco\Workflow\Domain\Enum\WorkflowRunStatus;
use Sofyco\Workflow\Tests\Fixtures\WebsitePostsParsingWorkflowFixture;
use Sofyco\Workflow\Tests\Support\WorkflowTestHarness;

final class WebsitePostsParsingWorkflowTest extends TestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/workflow_test_' . bin2hex(random_bytes(8));
        mkdir($this->storagePath, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->storagePath);
    }

    #[Test]
    public function itParsesWebsitePostsViaLlmPrompt(): void
    {
        $harness = new WorkflowTestHarness($this->storagePath);
        $harness->versions->save(WebsitePostsParsingWorkflowFixture::createVersion());

        $sourceUrl = 'https://example.com/blog';
        $pageHtml = WebsitePostsParsingWorkflowFixture::samplePageHtml();

        $run = $harness->runService->start(
            workflowId: WebsitePostsParsingWorkflowFixture::WORKFLOW_ID,
            userId: 'user_1',
            inputArtifacts: [
                'source_url' => [
                    'type' => 'file',
                    'mimeType' => 'text/plain',
                    'extension' => 'txt',
                    'content' => $sourceUrl,
                ],
                'page_html' => [
                    'type' => 'file',
                    'mimeType' => 'text/html',
                    'extension' => 'html',
                    'content' => $pageHtml,
                ],
            ],
        );

        $harness->runService->process($run->getId());

        $completedRun = $harness->runs->find($run->getId());
        self::assertNotNull($completedRun);
        self::assertSame(WorkflowRunStatus::Completed, $completedRun->getStatus());

        $executions = $harness->nodeExecutions->findByWorkflowRunId($run->getId());
        self::assertCount(3, $executions);

        foreach (['input_page', 'parse_posts', 'collect_output'] as $nodeId) {
            $nodeExecutions = array_filter(
                $executions,
                static fn ($execution): bool => $execution->getNodeId() === $nodeId,
            );
            self::assertCount(1, $nodeExecutions, sprintf('Expected one execution for node "%s".', $nodeId));
            self::assertSame(NodeExecutionStatus::Completed, array_values($nodeExecutions)[0]->getStatus());
        }

        self::assertCount(1, $harness->llmGateway->requests);
        $llmRequest = $harness->llmGateway->requests[0];
        self::assertSame('application/json', $llmRequest->responseMimeType);

        $userPrompt = $this->extractUserPrompt($llmRequest->messages);
        self::assertStringContainsString($sourceUrl, $userPrompt);
        self::assertStringContainsString('PHP 8.5 Features Overview', $userPrompt);
        self::assertStringContainsString('extract all posts', $userPrompt);

        $parseExecution = array_values(array_filter(
            $executions,
            static fn ($execution): bool => $execution->getNodeId() === 'parse_posts',
        ))[0];
        $postsReference = $parseExecution->getOutputArtifacts()[0];
        self::assertSame('posts', $postsReference->alias);
        self::assertSame('application/json', $postsReference->mimeType);

        $postsArtifact = $harness->artifacts->find($postsReference->artifactId);
        self::assertNotNull($postsArtifact);

        /** @var array{sourceUrl: string, posts: list<array{title: string, url: string, publishedAt: string, excerpt: string}>, total: int} $parsedPosts */
        $parsedPosts = json_decode($harness->artifactStorage->readContent($postsArtifact), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame($sourceUrl, $parsedPosts['sourceUrl']);
        self::assertSame(3, $parsedPosts['total']);
        self::assertCount(3, $parsedPosts['posts']);
        self::assertSame('PHP 8.5 Features Overview', $parsedPosts['posts'][0]['title']);
        self::assertSame('https://example.com/blog/php-8-5-features', $parsedPosts['posts'][0]['url']);
        self::assertSame('LLM-Assisted HTML Parsing', $parsedPosts['posts'][2]['title']);

        $finalExecution = array_values(array_filter(
            $executions,
            static fn ($execution): bool => $execution->getNodeId() === 'collect_output',
        ))[0];
        $resultReference = $finalExecution->getOutputArtifacts()[0];
        $resultArtifact = $harness->artifacts->find($resultReference->artifactId);
        self::assertNotNull($resultArtifact);

        /** @var array{posts: array{artifactId: string, type: string, mimeType: string}} $manifest */
        $manifest = json_decode($harness->artifactStorage->readContent($resultArtifact), true, flags: JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('posts', $manifest);
        self::assertSame('application/json', $manifest['posts']['mimeType']);
        self::assertSame($postsArtifact->getId(), $manifest['posts']['artifactId']);
    }

    /**
     * @param list<array<string, mixed>> $messages
     */
    private function extractUserPrompt(array $messages): string
    {
        foreach ($messages as $message) {
            if (($message['role'] ?? '') === 'user' && is_string($message['content'] ?? null)) {
                return $message['content'];
            }
        }

        return '';
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
