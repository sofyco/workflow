<?php declare(strict_types=1);

namespace Sofyco\Workflow\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sofyco\Workflow\Domain\Enum\ExecutionEventType;
use Sofyco\Workflow\Domain\Enum\NodeExecutionStatus;
use Sofyco\Workflow\Domain\Enum\WorkflowRunStatus;
use Sofyco\Workflow\Tests\Fixtures\VideoContentWorkflowFixture;
use Sofyco\Workflow\Tests\Support\WorkflowTestHarness;

final class VideoContentWorkflowTest extends TestCase
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
    public function itRunsComplexVideoContentWorkflowWithElevenLabsVoiceoverAndSubtitles(): void
    {
        $harness = new WorkflowTestHarness($this->storagePath);
        $harness->versions->save(VideoContentWorkflowFixture::createVersion());

        $run = $harness->runService->start(
            workflowId: VideoContentWorkflowFixture::WORKFLOW_ID,
            userId: 'user_1',
            inputArtifacts: [
                'source_description' => [
                    'type' => 'file',
                    'mimeType' => 'text/plain',
                    'extension' => 'txt',
                    'content' => 'A smart home system that automates lighting, climate, and security for modern apartments.',
                ],
            ],
        );

        $harness->runService->process($run->getId());

        $completedRun = $harness->runs->find($run->getId());
        self::assertNotNull($completedRun);
        self::assertSame(WorkflowRunStatus::Completed, $completedRun->getStatus());

        $executions = $harness->nodeExecutions->findByWorkflowRunId($run->getId());
        self::assertCount(6, $executions);

        foreach ([
            'input_text',
            'generate_script',
            'generate_subtitles',
            'elevenlabs_voiceover',
            'render_video',
            'collect_output',
        ] as $nodeId) {
            $nodeExecutions = array_filter(
                $executions,
                static fn ($execution): bool => $execution->getNodeId() === $nodeId,
            );
            self::assertCount(1, $nodeExecutions, sprintf('Expected one execution for node "%s".', $nodeId));
            self::assertSame(
                NodeExecutionStatus::Completed,
                array_values($nodeExecutions)[0]->getStatus(),
                sprintf('Node "%s" should complete successfully.', $nodeId),
            );
        }

        self::assertCount(2, $harness->llmGateway->requests);
        self::assertCount(1, $harness->elevenLabsGateway->calls);
        self::assertSame('elevenlabs', $harness->elevenLabsGateway->calls[0]['settings']['provider']);
        self::assertSame('Rachel', $harness->elevenLabsGateway->calls[0]['settings']['voice']);
        self::assertStringContainsString('Smart lighting adjusts', $harness->elevenLabsGateway->calls[0]['text']);

        self::assertCount(1, $harness->videoRenderer->calls);
        self::assertSame(30, $harness->videoRenderer->calls[0]['settings']['fps']);
        self::assertStringContainsString('00:00:00,000', $harness->videoRenderer->calls[0]['subtitlesContent']);

        $artifacts = $harness->artifacts->findByWorkflowRunId($run->getId());
        self::assertGreaterThanOrEqual(6, count($artifacts));

        $finalExecution = array_values(array_filter(
            $executions,
            static fn ($execution): bool => $execution->getNodeId() === 'collect_output',
        ))[0];
        $resultReference = $finalExecution->getOutputArtifacts()[0];
        $resultArtifact = $harness->artifacts->find($resultReference->artifactId);
        self::assertNotNull($resultArtifact);
        self::assertSame('application/json', $resultArtifact->getMimeType());

        /** @var array<string, array{artifactId: string, type: string, mimeType: string}> $manifest */
        $manifest = json_decode($harness->artifactStorage->readContent($resultArtifact), true, flags: JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('video', $manifest);
        self::assertArrayHasKey('script', $manifest);
        self::assertArrayHasKey('audio', $manifest);
        self::assertArrayHasKey('subtitles', $manifest);
        self::assertSame('video', $manifest['video']['type']);
        self::assertSame('video/mp4', $manifest['video']['mimeType']);
        self::assertSame('audio', $manifest['audio']['type']);
        self::assertSame('audio/mpeg', $manifest['audio']['mimeType']);

        $videoArtifact = $harness->artifacts->find($manifest['video']['artifactId']);
        self::assertNotNull($videoArtifact);
        self::assertTrue($videoArtifact->getMetadata()['subtitlesOverlay'] ?? false);

        $events = $harness->events->findByWorkflowRunId($run->getId());
        self::assertContains(
            ExecutionEventType::WorkflowStarted,
            array_map(static fn ($event) => $event->getType(), $events),
        );
        self::assertContains(
            ExecutionEventType::WorkflowCompleted,
            array_map(static fn ($event) => $event->getType(), $events),
        );
        self::assertContains(
            ExecutionEventType::ArtifactCreated,
            array_map(static fn ($event) => $event->getType(), $events),
        );
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
