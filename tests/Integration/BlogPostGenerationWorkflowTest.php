<?php declare(strict_types=1);

namespace Sofyco\Workflow\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sofyco\Workflow\Domain\Enum\NodeExecutionStatus;
use Sofyco\Workflow\Domain\Enum\WorkflowRunStatus;
use Sofyco\Workflow\Tests\Fixtures\BlogPostGenerationWorkflowFixture;
use Sofyco\Workflow\Tests\Fixtures\WebsitePostsParsingWorkflowFixture;
use Sofyco\Workflow\Tests\Support\WorkflowArtifactHelper;
use Sofyco\Workflow\Tests\Support\WorkflowTestHarness;

final class BlogPostGenerationWorkflowTest extends TestCase
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
    public function itGeneratesSitePostFromUpstreamWorkflowResultInParallel(): void
    {
        $harness = new WorkflowTestHarness($this->storagePath);

        $sourcePost = $this->runParsingWorkflowAndExtractFirstPost($harness);

        $harness->versions->save(BlogPostGenerationWorkflowFixture::createVersion());

        $llmRequestsBefore = count($harness->llmGateway->requests);

        $run = $harness->runService->start(
            workflowId: BlogPostGenerationWorkflowFixture::WORKFLOW_ID,
            userId: 'user_1',
            inputArtifacts: [
                'source_post' => BlogPostGenerationWorkflowFixture::sourcePostInput($sourcePost),
            ],
        );

        $harness->runService->process($run->getId());

        $completedRun = $harness->runs->find($run->getId());
        self::assertNotNull($completedRun);
        self::assertSame(WorkflowRunStatus::Completed, $completedRun->getStatus());

        $executions = $harness->nodeExecutions->findByWorkflowRunId($run->getId());
        self::assertCount(8, $executions);

        $parallelNodeIds = [
            'generate_title',
            'generate_description',
            'generate_body',
            'generate_tags',
            'generate_categories',
            'generate_cover_image',
        ];

        foreach (['input_source_post', ...$parallelNodeIds, 'collect_site_post'] as $nodeId) {
            $nodeExecutions = array_filter(
                $executions,
                static fn ($execution): bool => $execution->getNodeId() === $nodeId,
            );
            self::assertCount(1, $nodeExecutions, sprintf('Expected one execution for node "%s".', $nodeId));
            self::assertSame(NodeExecutionStatus::Completed, array_values($nodeExecutions)[0]->getStatus());
        }

        self::assertSame(5, count($harness->llmGateway->requests) - $llmRequestsBefore);
        self::assertCount(1, $harness->imageGenerator->calls);
        self::assertStringContainsString($sourcePost['excerpt'], $harness->imageGenerator->calls[0]['prompt']);

        $titleArtifact = WorkflowArtifactHelper::findNodeOutputArtifact($harness, $run->getId(), 'generate_title', 'title');
        self::assertStringContainsString('PHP 8.5', $harness->artifactStorage->readContent($titleArtifact));

        $tagsArtifact = WorkflowArtifactHelper::findNodeOutputArtifact($harness, $run->getId(), 'generate_tags', 'tags');
        /** @var array{tags: list<string>} $tagsPayload */
        $tagsPayload = json_decode($harness->artifactStorage->readContent($tagsArtifact), true, flags: JSON_THROW_ON_ERROR);
        self::assertContains('php-8-5', $tagsPayload['tags']);

        $categoriesArtifact = WorkflowArtifactHelper::findNodeOutputArtifact($harness, $run->getId(), 'generate_categories', 'categories');
        /** @var array{categories: list<string>} $categoriesPayload */
        $categoriesPayload = json_decode($harness->artifactStorage->readContent($categoriesArtifact), true, flags: JSON_THROW_ON_ERROR);
        self::assertContains('PHP', $categoriesPayload['categories']);

        $coverArtifact = WorkflowArtifactHelper::findNodeOutputArtifact($harness, $run->getId(), 'generate_cover_image', 'cover_image');
        self::assertSame('image/png', $coverArtifact->getMimeType());

        $finalArtifact = WorkflowArtifactHelper::findNodeOutputArtifact($harness, $run->getId(), 'collect_site_post', 'result');
        /** @var array<string, array{artifactId: string, type: string, mimeType: string, storagePath: string}> $sitePost */
        $sitePost = json_decode($harness->artifactStorage->readContent($finalArtifact), true, flags: JSON_THROW_ON_ERROR);

        foreach (['title', 'description', 'body', 'tags', 'categories', 'cover_image'] as $field) {
            self::assertArrayHasKey($field, $sitePost, sprintf('Site post manifest missing field "%s".', $field));
        }

        self::assertSame('text/plain', $sitePost['title']['mimeType']);
        self::assertSame('application/json', $sitePost['tags']['mimeType']);
        self::assertSame('image', $sitePost['cover_image']['type']);
        self::assertSame($coverArtifact->getId(), $sitePost['cover_image']['artifactId']);
    }

    /**
     * @return array{title: string, url: string, publishedAt: string, excerpt: string}
     */
    private function runParsingWorkflowAndExtractFirstPost(WorkflowTestHarness $harness): array
    {
        $harness->versions->save(WebsitePostsParsingWorkflowFixture::createVersion());

        $parsingRun = $harness->runService->start(
            workflowId: WebsitePostsParsingWorkflowFixture::WORKFLOW_ID,
            userId: 'user_1',
            inputArtifacts: [
                'source_url' => [
                    'type' => 'file',
                    'mimeType' => 'text/plain',
                    'extension' => 'txt',
                    'content' => 'https://example.com/blog',
                ],
                'page_html' => [
                    'type' => 'file',
                    'mimeType' => 'text/html',
                    'extension' => 'html',
                    'content' => WebsitePostsParsingWorkflowFixture::samplePageHtml(),
                ],
            ],
        );

        $harness->runService->process($parsingRun->getId());

        return WorkflowArtifactHelper::extractFirstPostFromParsingRun($harness, $parsingRun->getId());
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
