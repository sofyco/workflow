# Sofyco Workflow

[![CI](https://github.com/sofyco/workflow/actions/workflows/ci.yaml/badge.svg)](https://github.com/sofyco/workflow/actions/workflows/ci.yaml)
[![codecov](https://codecov.io/gh/sofyco/workflow/branch/main/graph/badge.svg)](https://codecov.io/gh/sofyco/workflow)
[![Latest Stable Version](https://poser.pugx.org/sofyco/workflow/v)](https://packagist.org/packages/sofyco/workflow)
[![License](https://poser.pugx.org/sofyco/workflow/license)](https://packagist.org/packages/sofyco/workflow)

A PHP library for building **artifact-based workflow graphs** — not linear prompt chains, but directed graphs where nodes consume and produce typed files.

Use it to orchestrate multimodal pipelines: LLM prompts, text-to-speech, image generation, video rendering, conditional branches, parallel fan-out/fan-in, and chained workflows.

## Features

- **Graph execution engine** — schedules all ready nodes in parallel, supports fan-in and fan-out
- **Artifact-first model** — text, JSON, images, audio, and video are stored as files with MIME types
- **Immutable execution history** — every node attempt creates a new `NodeExecution`; results are never overwritten
- **Typed ports** — connect nodes through named input/output ports with artifact type and MIME validation
- **Safe conditions** — edge conditions use a small DSL, not arbitrary code
- **Pluggable providers** — swap LLM, TTS, image, and video backends via interfaces
- **Framework-friendly** — pure PHP core with optional Symfony Messenger integration
- **PHP 8.5+** — enums, readonly classes, strict types throughout

## Requirements

- PHP `^8.5`

No framework is required. Symfony Messenger and Doctrine adapters are provided as integration stubs.

## Installation

```bash
composer require sofyco/workflow
```

## Core concepts

### Workflow graph

A workflow is a directed graph made of:

| Concept | Description |
|---------|-------------|
| `Workflow` | User-owned template (name, status, latest version) |
| `WorkflowVersion` | Immutable published snapshot of the graph used at runtime |
| `WorkflowNode` | A step in the graph (input, prompt, TTS, etc.) |
| `WorkflowPort` | Typed input or output slot on a node |
| `WorkflowEdge` | Connection from `NodeA.outputPort` → `NodeB.inputPort` |
| `WorkflowRun` | A single execution of a published version |
| `NodeExecution` | One attempt to run a node within a run |
| `Artifact` | A file produced or consumed during execution |
| `ExecutionEvent` | Timeline entry for debugging and analytics |

### Artifacts

An **artifact** is always a physical file (or a reference to one in object storage). Format is determined by `mimeType`, not by a separate “text vs JSON” type.

```php
enum ArtifactType: string
{
    case Image = 'image';
    case Audio = 'audio';
    case Video = 'video';
    case File  = 'file';  // text, JSON, HTML, Markdown, CSV, XML, …
}
```

Examples:

| Content | `ArtifactType` | `mimeType` |
|---------|----------------|------------|
| Plain text | `File` | `text/plain` |
| JSON result | `File` | `application/json` |
| MP3 voiceover | `Audio` | `audio/mpeg` |
| PNG cover image | `Image` | `image/png` |
| MP4 video | `Video` | `video/mp4` |

**Rules:**

1. Nodes never return raw strings — they create artifacts.
2. Artifacts are immutable once written.
3. Retries create new `NodeExecution` records with a new `attempt` number.
4. MongoDB (or any DB) stores metadata only; file content lives in object storage.

### Node readiness

A node runs when:

1. It is reachable from the start node.
2. All **required** input ports have compatible artifacts.
3. Incoming edge conditions are satisfied.
4. It is not already running or completed.
5. Retry limits have not been exceeded.

After one node finishes, the scheduler finds **all** ready nodes — not just the next one — enabling natural parallelism.

## Architecture

```
src/
├── Domain/
│   ├── Enum/          # NodeType, ArtifactType, statuses, …
│   └── Model/         # Workflow, WorkflowVersion, Artifact, …
├── Application/
│   ├── Workflow/      # WorkflowService, WorkflowValidator
│   ├── Runtime/       # Scheduler, execution engine, resolvers
│   ├── NodeRunner/    # Per-node-type executors
│   ├── Artifact/      # Storage and content reader
│   ├── Llm/           # LLM gateway, prompt rendering
│   ├── Tts/           # Text-to-speech gateway registry
│   ├── Image/         # Image generation gateway registry
│   └── Video/         # Video renderer interface
├── Infrastructure/
│   ├── InMemory/      # In-memory repositories (tests, prototyping)
│   ├── Storage/       # Local and S3 artifact storage
│   ├── Doctrine/      # Repository interfaces and stubs
│   └── Messenger/     # Symfony Messenger message + handler
```

### Runtime services

| Service | Role |
|---------|------|
| `WorkflowRunService` | Starts a run, stores input artifacts, kicks the scheduler |
| `WorkflowScheduler` | Finds all ready nodes and dispatches execution |
| `WorkflowExecutionService` | Runs one node (idempotent via `executionKey`) |
| `ArtifactResolver` | Maps edge outputs to node input ports |
| `NodeReadinessResolver` | Checks required ports and artifact compatibility |
| `ConditionEvaluator` | Evaluates edge conditions against runtime context |
| `CompletionResolver` | Marks the run completed or failed |
| `RuntimeContextBuilder` | Builds prompt/condition context from artifacts |
| `ExecutionEventRecorder` | Writes timeline events |

### Node runners

| `NodeType` | Runner | Status |
|------------|--------|--------|
| `input` | `InputNodeRunner` | Implemented |
| `prompt` | `PromptNodeRunner` | Implemented |
| `text_to_speech` | `TextToSpeechNodeRunner` | Implemented |
| `image_generation` | `ImageGenerationNodeRunner` | Implemented |
| `video_render` | `VideoRenderNodeRunner` | Implemented |
| `final_output` | `FinalOutputNodeRunner` | Implemented |
| `condition` | `ConditionNodeRunner` | Stub |
| `validator`, `transform`, `merge`, … | — | Planned |

Register runners in `NodeRunnerRegistry`:

```php
$registry = new NodeRunnerRegistry([
    new InputNodeRunner(),
    new PromptNodeRunner($storage, $promptRenderer, $llmGateway),
    new TextToSpeechNodeRunner($contentReader, $storage, $ttsRegistry),
    new ImageGenerationNodeRunner($contentReader, $storage, $imageRegistry),
    new VideoRenderNodeRunner($storage, $videoRenderer),
    new FinalOutputNodeRunner($storage),
]);
```

## Quick start

The example below runs a workflow synchronously using in-memory repositories and local file storage. See `tests/Support/WorkflowTestHarness.php` for a complete wiring reference.

```php
use Sofyco\Workflow\Application\Artifact\ArtifactContentReader;
use Sofyco\Workflow\Application\Id\UuidIdGenerator;
use Sofyco\Workflow\Application\Llm\PromptRenderer;
use Sofyco\Workflow\Application\NodeRunner\FinalOutputNodeRunner;
use Sofyco\Workflow\Application\NodeRunner\InputNodeRunner;
use Sofyco\Workflow\Application\NodeRunner\NodeRunnerRegistry;
use Sofyco\Workflow\Application\NodeRunner\PromptNodeRunner;
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
use Sofyco\Workflow\Infrastructure\InMemory\InMemoryArtifactRepository;
use Sofyco\Workflow\Infrastructure\InMemory\InMemoryExecutionEventRepository;
use Sofyco\Workflow\Infrastructure\InMemory\InMemoryNodeExecutionRepository;
use Sofyco\Workflow\Infrastructure\InMemory\InMemoryWorkflowRunRepository;
use Sofyco\Workflow\Infrastructure\InMemory\InMemoryWorkflowVersionRepository;
use Sofyco\Workflow\Infrastructure\Storage\LocalArtifactStorage;

$idGenerator = new UuidIdGenerator();
$runs = new InMemoryWorkflowRunRepository();
$versions = new InMemoryWorkflowVersionRepository();
$nodeExecutions = new InMemoryNodeExecutionRepository();
$artifacts = new InMemoryArtifactRepository();
$events = new InMemoryExecutionEventRepository($idGenerator);
$storage = new LocalArtifactStorage('/tmp/workflow-artifacts', $idGenerator);
$contentReader = new ArtifactContentReader($storage);

// … wire gateways, node runners, runtime services (see WorkflowTestHarness)

$versions->save($myWorkflowVersion);

$run = $runService->start(
    workflowId: 'wf_example',
    userId: 'user_1',
    inputArtifacts: [
        'source_text' => [
            'type' => 'file',
            'mimeType' => 'text/plain',
            'extension' => 'txt',
            'content' => 'Write a blog post about PHP 8.5.',
        ],
    ],
);

$runService->process($run->getId());
// Run status is now Completed; artifacts are in $artifacts repository
```

### Input artifact format

When starting a run, pass input artifacts as an associative array:

```php
[
    'alias_name' => [
        'type'      => 'file',           // ArtifactType value
        'mimeType'  => 'text/plain',
        'extension' => 'txt',
        'content'   => '…',              // stored as a file
    ],
]
```

### Idempotency

Each node execution uses a unique key:

```
{workflowRunId}:{nodeId}:{attempt}
```

If Messenger redelivers a message, an already-completed execution is returned without re-running.

## Building a workflow graph

```php
use Sofyco\Workflow\Domain\Model\WorkflowEdge;
use Sofyco\Workflow\Domain\Model\WorkflowNode;
use Sofyco\Workflow\Domain\Model\WorkflowPort;
use Sofyco\Workflow\Domain\Model\WorkflowVersion;
use Sofyco\Workflow\Domain\Enum\ArtifactType;
use Sofyco\Workflow\Domain\Enum\NodeType;

$input = new WorkflowNode('input_text', NodeType::Input, 'Input');
$input->addOutputPort(new WorkflowPort(
    name: 'source_text',
    type: ArtifactType::File,
    allowedMimeTypes: ['text/plain'],
));

$prompt = new WorkflowNode('rewrite', NodeType::Prompt, 'Rewrite');
$prompt->addInputPort(new WorkflowPort(
    name: 'source_text',
    type: ArtifactType::File,
    allowedMimeTypes: ['text/plain'],
));
$prompt->addOutputPort(new WorkflowPort(
    name: 'story',
    type: ArtifactType::File,
    allowedMimeTypes: ['text/plain'],
));
$prompt->setSettings([
    'model' => 'gpt-4.1-mini',
    'systemPrompt' => 'You rewrite text clearly.',
    'userPromptTemplate' => '{{ input.source_text.content }}',
    'responseMimeType' => 'text/plain',
    'outputAlias' => 'story',
]);

$version = new WorkflowVersion(
    id: 'wfv_1',
    workflowId: 'wf_1',
    version: 1,
    name: 'Rewrite Pipeline',
    startNodeId: 'input_text',
    createdAt: new DateTimeImmutable(),
);

$version->addNode($input);
$version->addNode($prompt);
$version->addEdge(new WorkflowEdge(
    id: 'edge_1',
    fromNodeId: 'input_text',
    fromPort: 'source_text',
    toNodeId: 'rewrite',
    toPort: 'source_text',
));
```

### Prompt templates

`PromptRenderer` replaces `{{ dotted.path }}` placeholders using the runtime context:

```json
{
  "input": {
    "source_text": {
      "artifactId": "…",
      "mimeType": "text/plain",
      "content": "Hello world"
    }
  },
  "nodes": {
    "rewrite": {
      "latest": {
        "output": {
          "story": { "content": "…" }
        }
      }
    }
  }
}
```

Template example:

```
Rewrite this text:

{{ input.source_text.content }}
```

### Edge conditions

Conditions use a safe DSL — no user PHP:

```php
new ConditionDefinition(
    field: 'nodes.validate.latest.output.validation_result.content.is_valid',
    operator: ConditionOperator::Equals,
    value: true,
);
```

Supported operators: `equals`, `not_equals`, `greater_than`, `less_than`, `contains`, `exists`.

## Extending the library

### LLM gateway

```php
interface LlmGatewayInterface
{
    public function complete(LlmRequest $request): LlmResponse;
}
```

Used by `PromptNodeRunner` to generate text or JSON artifacts.

### TTS gateway

```php
interface TtsGatewayInterface
{
    public function supports(string $provider): bool;
    public function synthesize(string $text, array $settings): TtsSynthesisResult;
}
```

Register providers in `TtsGatewayRegistry`. Example node settings for ElevenLabs:

```php
[
    'provider' => 'elevenlabs',
    'model'    => 'eleven_multilingual_v2',
    'voice'    => 'Rachel',
    'format'   => 'mp3',
]
```

### Image generator

```php
interface ImageGeneratorInterface
{
    public function supports(string $provider): bool;
    public function generate(string $prompt, array $settings): ImageGenerationResult;
}
```

`ImageGenerationNodeRunner` can read a text prompt or extract a field from a JSON artifact:

```php
[
    'provider'    => 'openai',
    'model'       => 'gpt-image-1',
    'inputPort'   => 'source_post',
    'promptField' => 'excerpt',   // when input is application/json
    'outputAlias' => 'cover_image',
]
```

### Video renderer

```php
interface VideoRendererInterface
{
    public function render(
        string $audioPath,
        string $subtitlesPath,
        ?string $backgroundPath,
        array $settings,
    ): VideoRenderResult;
}
```

`VideoRenderNodeRunner` expects `audio` and `subtitles` input ports and produces a `video/mp4` artifact.

### Artifact storage

```php
interface ArtifactStorageInterface
{
    public function storeFile(…): Artifact;
    public function storeContent(…): Artifact;
    public function readContent(Artifact $artifact): string;
    public function createTemporaryLocalFile(Artifact $artifact): string;
}
```

`LocalArtifactStorage` is included for development. Implement `S3ArtifactStorage` for production object storage.

Recommended storage paths:

```
workflow-runs/{runId}/input/{artifactId}/{filename}
workflow-runs/{runId}/nodes/{nodeId}/{executionId}/{artifactId}/{filename}
```

### Async execution (Symfony Messenger)

For production, dispatch one message per node instead of calling `process()` synchronously:

```php
final readonly class ExecuteWorkflowNode
{
    public function __construct(
        public string $workflowRunId,
        public string $nodeId,
        public int $attempt,
    ) {}
}
```

The handler runs the node and calls `WorkflowScheduler::scheduleReadyNodes()` again.

## Example workflows

The test suite includes three end-to-end workflow examples:

### 1. Video content production

```
Input → Generate Script → Generate Subtitles ─┐
                         → ElevenLabs TTS  ──┼→ Render Video → Final Output
```

Fixture: `tests/Fixtures/VideoContentWorkflowFixture.php`  
Test: `tests/Integration/VideoContentWorkflowTest.php`

### 2. Website posts parsing

```
Input (URL + HTML) → Parse Posts (LLM → JSON) → Final Output
```

Fixture: `tests/Fixtures/WebsitePostsParsingWorkflowFixture.php`  
Test: `tests/Integration/WebsitePostsParsingWorkflowTest.php`

### 3. Blog post generation (chained workflow)

Takes a post from the parsing workflow and generates a full site post in parallel:

```
Input (source post JSON)
    ├── Generate Title
    ├── Generate Description
    ├── Generate Body
    ├── Generate Tags
    ├── Generate Categories
    └── Generate Cover Image
            ↓
    Collect Site Post (JSON manifest)
```

Fixture: `tests/Fixtures/BlogPostGenerationWorkflowFixture.php`  
Test: `tests/Integration/BlogPostGenerationWorkflowTest.php`

The blog post test demonstrates **workflow chaining**: it runs the parsing workflow first, extracts the first post artifact, and feeds it into the generation workflow.

## Symfony integration (optional)

The library ships stubs for Symfony Messenger and Doctrine repositories:

| Component | Namespace |
|-----------|-----------|
| Repositories | `Sofyco\Workflow\Infrastructure\Doctrine\*Repository` |
| Messenger | `Sofyco\Workflow\Infrastructure\Messenger\ExecuteWorkflowNode` |

Wire these in your Symfony app via service configuration. The core engine has no Symfony dependency. HTTP endpoints for a workflow builder UI belong in your application layer, not in this library.

## Development

Clone the repository and run tests with Docker:

```bash
make composer-update
make phpunit
make phpstan
```

Or without Docker:

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse
```

### Project structure (tests)

```
tests/
├── Fixtures/       # Workflow graph definitions
├── Integration/    # End-to-end workflow tests
└── Support/        # Test harness, fake gateways
```

## Design principles

1. **Nodes produce artifacts, not strings.**
2. **Text and JSON are `File` artifacts** with the appropriate MIME type.
3. **WorkflowVersion is immutable** — runs always execute against the version they started with.
4. **Parallelism is native** — the scheduler runs every ready node, not just one.
5. **Retries are new attempts** — history is preserved for auditing and debugging.
6. **Conditions are safe** — limited DSL, no eval or user code.
7. **Loops must be bounded** — configure `maxTotalNodeExecutions` and `maxAttemptsPerNode` on the workflow version.

## Roadmap

- [ ] `WorkflowService` — create, edit, and publish workflow drafts
- [ ] `WorkflowValidator` — graph validation before publish
- [ ] Doctrine MongoDB repository implementations
- [ ] S3 / object storage adapter
- [ ] Condition, validator, transform, and merge node runners
- [ ] Human review and webhook nodes
- [ ] Sub-workflows

## License

This package is open-source software licensed under the [MIT license](LICENSE).

## Author

Sofiia Korzhova — [sofykorzhova@gmail.com](mailto:sofykorzhova@gmail.com)
