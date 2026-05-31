<?php declare(strict_types=1);

namespace Sofyco\Workflow\Tests\Fixtures;

use DateTimeImmutable;
use Sofyco\Workflow\Domain\Model\WorkflowEdge;
use Sofyco\Workflow\Domain\Model\WorkflowNode;
use Sofyco\Workflow\Domain\Model\WorkflowPort;
use Sofyco\Workflow\Domain\Model\WorkflowVersion;
use Sofyco\Workflow\Domain\Enum\ArtifactType;
use Sofyco\Workflow\Domain\Enum\NodeType;

final class WebsitePostsParsingWorkflowFixture
{
    public const string WORKFLOW_ID = 'wf_website_posts_parsing';
    public const string VERSION_ID = 'wfv_website_posts_parsing_v1';

    public static function createVersion(): WorkflowVersion
    {
        $version = new WorkflowVersion(
            id: self::VERSION_ID,
            workflowId: self::WORKFLOW_ID,
            version: 1,
            name: 'Website Posts Parsing',
            startNodeId: 'input_page',
            createdAt: new DateTimeImmutable(),
            publishedAt: new DateTimeImmutable(),
        );

        foreach (self::nodes() as $node) {
            $version->addNode($node);
        }

        foreach (self::edges() as $edge) {
            $version->addEdge($edge);
        }

        return $version;
    }

    /**
     * @return list<WorkflowNode>
     */
    private static function nodes(): array
    {
        $input = new WorkflowNode(
            id: 'input_page',
            type: NodeType::Input,
            name: 'Input Page Source',
        );
        $input->addOutputPort(new WorkflowPort(
            name: 'source_url',
            type: ArtifactType::File,
            allowedMimeTypes: ['text/plain'],
        ));
        $input->addOutputPort(new WorkflowPort(
            name: 'page_html',
            type: ArtifactType::File,
            allowedMimeTypes: ['text/html'],
        ));

        $parsePosts = new WorkflowNode(
            id: 'parse_posts',
            type: NodeType::Prompt,
            name: 'Parse Website Posts',
        );
        $parsePosts->addInputPort(new WorkflowPort(
            name: 'source_url',
            type: ArtifactType::File,
            allowedMimeTypes: ['text/plain'],
        ));
        $parsePosts->addInputPort(new WorkflowPort(
            name: 'page_html',
            type: ArtifactType::File,
            allowedMimeTypes: ['text/html'],
        ));
        $parsePosts->addOutputPort(new WorkflowPort(
            name: 'posts',
            type: ArtifactType::File,
            allowedMimeTypes: ['application/json'],
        ));
        $parsePosts->setSettings([
            'provider' => 'openai',
            'model' => 'gpt-4.1-mini',
            'systemPrompt' => 'You extract structured blog post data from HTML pages. Return only valid JSON.',
            'userPromptTemplate' => <<<'PROMPT'
Parse the website {{ input.source_url.content }} and extract all posts from this HTML.

Return JSON with fields: sourceUrl, posts (array of title, url, publishedAt, excerpt), total.

HTML:
{{ input.page_html.content }}
PROMPT,
            'responseMimeType' => 'application/json',
            'outputAlias' => 'posts',
            'jsonSchema' => [
                'type' => 'object',
                'required' => ['sourceUrl', 'posts', 'total'],
            ],
        ]);

        $finalOutput = new WorkflowNode(
            id: 'collect_output',
            type: NodeType::FinalOutput,
            name: 'Collect Parsed Posts',
        );
        $finalOutput->addInputPort(new WorkflowPort(
            name: 'posts',
            type: ArtifactType::File,
            required: true,
            allowedMimeTypes: ['application/json'],
        ));
        $finalOutput->addOutputPort(new WorkflowPort(
            name: 'result',
            type: ArtifactType::File,
            allowedMimeTypes: ['application/json'],
        ));

        return [$input, $parsePosts, $finalOutput];
    }

    /**
     * @return list<WorkflowEdge>
     */
    private static function edges(): array
    {
        return [
            new WorkflowEdge(
                id: 'edge_url_to_parse',
                fromNodeId: 'input_page',
                fromPort: 'source_url',
                toNodeId: 'parse_posts',
                toPort: 'source_url',
            ),
            new WorkflowEdge(
                id: 'edge_html_to_parse',
                fromNodeId: 'input_page',
                fromPort: 'page_html',
                toNodeId: 'parse_posts',
                toPort: 'page_html',
            ),
            new WorkflowEdge(
                id: 'edge_posts_to_final',
                fromNodeId: 'parse_posts',
                fromPort: 'posts',
                toNodeId: 'collect_output',
                toPort: 'posts',
            ),
        ];
    }

    public static function samplePageHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head><title>Dev Blog</title></head>
<body>
<main>
  <article class="post">
    <h2><a href="/blog/php-8-5-features">PHP 8.5 Features Overview</a></h2>
    <time datetime="2026-03-01">March 1, 2026</time>
    <p>A quick look at the most useful additions in PHP 8.5.</p>
  </article>
  <article class="post">
    <h2><a href="/blog/workflow-engines">Building Workflow Engines</a></h2>
    <time datetime="2026-02-15">February 15, 2026</time>
    <p>How to design artifact-based workflow graphs for multimodal pipelines.</p>
  </article>
  <article class="post">
    <h2><a href="/blog/llm-parsing">LLM-Assisted HTML Parsing</a></h2>
    <time datetime="2026-01-20">January 20, 2026</time>
    <p>Using prompts to extract structured data from messy page markup.</p>
  </article>
</main>
</body>
</html>
HTML;
    }
}
