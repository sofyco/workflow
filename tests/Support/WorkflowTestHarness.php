<?php declare(strict_types=1);

namespace Sofyco\Workflow\Tests\Support;

use Sofyco\Workflow\Application\Artifact\ArtifactContentReader;
use Sofyco\Workflow\Application\Id\UuidIdGenerator;
use Sofyco\Workflow\Application\Llm\PromptRenderer;
use Sofyco\Workflow\Application\Image\ImageGeneratorRegistry;
use Sofyco\Workflow\Application\NodeRunner\FinalOutputNodeRunner;
use Sofyco\Workflow\Application\NodeRunner\ImageGenerationNodeRunner;
use Sofyco\Workflow\Application\NodeRunner\InputNodeRunner;
use Sofyco\Workflow\Application\NodeRunner\PromptNodeRunner;
use Sofyco\Workflow\Application\NodeRunner\NodeRunnerRegistry;
use Sofyco\Workflow\Application\NodeRunner\TextToSpeechNodeRunner;
use Sofyco\Workflow\Application\NodeRunner\VideoRenderNodeRunner;
use Sofyco\Workflow\Application\Runtime\ArtifactResolver;
use Sofyco\Workflow\Application\Runtime\CompletionResolver;
use Sofyco\Workflow\Application\Runtime\ConditionEvaluator;
use Sofyco\Workflow\Application\Runtime\ExecutionEventRecorder;
use Sofyco\Workflow\Application\Runtime\FieldAccessor;
use Sofyco\Workflow\Application\Runtime\NodeReadinessResolver;
use Sofyco\Workflow\Application\Runtime\RuntimeContextBuilder;
use Sofyco\Workflow\Application\Runtime\TransitionResolver;
use Sofyco\Workflow\Application\Runtime\WorkflowExecutionService;
use Sofyco\Workflow\Application\Runtime\WorkflowRunService;
use Sofyco\Workflow\Application\Runtime\WorkflowScheduler;
use Sofyco\Workflow\Application\Tts\TtsGatewayRegistry;
use Sofyco\Workflow\Infrastructure\InMemory\InMemoryArtifactRepository;
use Sofyco\Workflow\Infrastructure\InMemory\InMemoryExecutionEventRepository;
use Sofyco\Workflow\Infrastructure\InMemory\InMemoryNodeExecutionRepository;
use Sofyco\Workflow\Infrastructure\InMemory\InMemoryWorkflowRunRepository;
use Sofyco\Workflow\Infrastructure\InMemory\InMemoryWorkflowVersionRepository;
use Sofyco\Workflow\Infrastructure\Storage\LocalArtifactStorage;

final class WorkflowTestHarness
{
    public readonly InMemoryWorkflowRunRepository $runs;
    public readonly InMemoryWorkflowVersionRepository $versions;
    public readonly InMemoryNodeExecutionRepository $nodeExecutions;
    public readonly InMemoryArtifactRepository $artifacts;
    public readonly InMemoryExecutionEventRepository $events;
    public readonly LocalArtifactStorage $artifactStorage;
    public readonly WorkflowRunService $runService;
    public readonly FakeLlmGateway $llmGateway;
    public readonly FakeElevenLabsTtsGateway $elevenLabsGateway;
    public readonly FakeVideoRenderer $videoRenderer;
    public readonly FakeImageGenerator $imageGenerator;

    public function __construct(string $storagePath)
    {
        $idGenerator = new UuidIdGenerator();
        $this->runs = new InMemoryWorkflowRunRepository();
        $this->versions = new InMemoryWorkflowVersionRepository();
        $this->nodeExecutions = new InMemoryNodeExecutionRepository();
        $this->artifacts = new InMemoryArtifactRepository();
        $this->events = new InMemoryExecutionEventRepository($idGenerator);
        $this->artifactStorage = new LocalArtifactStorage($storagePath, $idGenerator);
        $this->llmGateway = new FakeLlmGateway();
        $this->elevenLabsGateway = new FakeElevenLabsTtsGateway();
        $this->videoRenderer = new FakeVideoRenderer();
        $this->imageGenerator = new FakeImageGenerator();

        $artifactContentReader = new ArtifactContentReader($this->artifactStorage);
        $artifactResolver = new ArtifactResolver($this->nodeExecutions, $this->artifacts);
        $fieldAccessor = new FieldAccessor();
        $conditionEvaluator = new ConditionEvaluator($fieldAccessor);
        $transitionResolver = new TransitionResolver($conditionEvaluator);
        $nodeReadinessResolver = new NodeReadinessResolver($artifactResolver);
        $runtimeContextBuilder = new RuntimeContextBuilder(
            $this->nodeExecutions,
            $this->artifacts,
            $artifactContentReader,
        );
        $eventRecorder = new ExecutionEventRecorder($this->events, $idGenerator);

        $nodeRunners = new NodeRunnerRegistry([
            new InputNodeRunner(),
            new PromptNodeRunner(
                $this->artifactStorage,
                new PromptRenderer(),
                $this->llmGateway,
            ),
            new TextToSpeechNodeRunner(
                $artifactContentReader,
                $this->artifactStorage,
                new TtsGatewayRegistry([$this->elevenLabsGateway]),
            ),
            new VideoRenderNodeRunner($this->artifactStorage, $this->videoRenderer),
            new ImageGenerationNodeRunner(
                $artifactContentReader,
                $this->artifactStorage,
                new ImageGeneratorRegistry([$this->imageGenerator]),
            ),
            new FinalOutputNodeRunner($this->artifactStorage),
        ]);

        $executionService = new WorkflowExecutionService(
            runs: $this->runs,
            versions: $this->versions,
            nodeExecutions: $this->nodeExecutions,
            artifacts: $this->artifacts,
            nodeRunners: $nodeRunners,
            artifactResolver: $artifactResolver,
            runtimeContextBuilder: $runtimeContextBuilder,
            eventRecorder: $eventRecorder,
            idGenerator: $idGenerator,
        );

        $scheduler = new WorkflowScheduler(
            runs: $this->runs,
            versions: $this->versions,
            nodeExecutions: $this->nodeExecutions,
            nodeReadinessResolver: $nodeReadinessResolver,
            transitionResolver: $transitionResolver,
            executionService: $executionService,
            completionResolver: new CompletionResolver($this->nodeExecutions),
        );

        $this->runService = new WorkflowRunService(
            versions: $this->versions,
            runs: $this->runs,
            artifacts: $this->artifacts,
            artifactStorage: $this->artifactStorage,
            scheduler: $scheduler,
            eventRecorder: $eventRecorder,
            idGenerator: $idGenerator,
        );
    }
}
