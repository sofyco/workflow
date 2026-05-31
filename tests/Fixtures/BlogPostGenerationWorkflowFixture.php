<?php declare(strict_types=1);

namespace Sofyco\Workflow\Tests\Fixtures;

use DateTimeImmutable;
use Sofyco\Workflow\Domain\Model\WorkflowEdge;
use Sofyco\Workflow\Domain\Model\WorkflowNode;
use Sofyco\Workflow\Domain\Model\WorkflowPort;
use Sofyco\Workflow\Domain\Model\WorkflowVersion;
use Sofyco\Workflow\Domain\Enum\ArtifactType;
use Sofyco\Workflow\Domain\Enum\NodeType;

final class BlogPostGenerationWorkflowFixture
{
    public const string WORKFLOW_ID = 'wf_blog_post_generation';
    public const string VERSION_ID = 'wfv_blog_post_generation_v1';

    public static function createVersion(): WorkflowVersion
    {
        $version = new WorkflowVersion(
            id: self::VERSION_ID,
            workflowId: self::WORKFLOW_ID,
            version: 1,
            name: 'Blog Post Generation',
            startNodeId: 'input_source_post',
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
     * @param array{title: string, url: string, publishedAt: string, excerpt: string} $post
     *
     * @return array{type: string, mimeType: string, extension: string, content: string}
     */
    public static function sourcePostInput(array $post): array
    {
        return [
            'type' => 'file',
            'mimeType' => 'application/json',
            'extension' => 'json',
            'content' => json_encode($post, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * @return list<WorkflowNode>
     */
    private static function nodes(): array
    {
        $input = new WorkflowNode(
            id: 'input_source_post',
            type: NodeType::Input,
            name: 'Input Source Post',
        );
        $input->addOutputPort(new WorkflowPort(
            name: 'source_post',
            type: ArtifactType::File,
            allowedMimeTypes: ['application/json'],
        ));

        $generateTitle = self::promptNode(
            id: 'generate_title',
            name: 'Generate Title',
            outputPort: 'title',
            outputMimeType: 'text/plain',
            systemPrompt: 'You write compelling blog post titles.',
            userPromptTemplate: 'Generate a concise blog post title based on this source post:\n\nTitle: {{ input.source_post.content.title }}\nExcerpt: {{ input.source_post.content.excerpt }}',
            responseMimeType: 'text/plain',
        );

        $generateDescription = self::promptNode(
            id: 'generate_description',
            name: 'Generate Description',
            outputPort: 'description',
            outputMimeType: 'text/plain',
            systemPrompt: 'You write SEO meta descriptions for blog posts.',
            userPromptTemplate: 'Generate a short meta description (max 160 chars) for this post:\n\n{{ input.source_post.content.excerpt }}',
            responseMimeType: 'text/plain',
        );

        $generateBody = self::promptNode(
            id: 'generate_body',
            name: 'Generate Body Text',
            outputPort: 'body',
            outputMimeType: 'text/plain',
            systemPrompt: 'You expand blog excerpts into full articles.',
            userPromptTemplate: 'Write a full blog post article based on:\n\nTitle: {{ input.source_post.content.title }}\nExcerpt: {{ input.source_post.content.excerpt }}',
            responseMimeType: 'text/plain',
        );

        $generateTags = self::promptNode(
            id: 'generate_tags',
            name: 'Generate Tags',
            outputPort: 'tags',
            outputMimeType: 'application/json',
            systemPrompt: 'You generate SEO tags for blog posts. Return JSON only.',
            userPromptTemplate: 'Generate SEO tags as JSON object {"tags": ["tag1", "tag2"]} for:\n\n{{ input.source_post.content.title }}\n{{ input.source_post.content.excerpt }}',
            responseMimeType: 'application/json',
        );

        $generateCategories = self::promptNode(
            id: 'generate_categories',
            name: 'Generate Categories',
            outputPort: 'categories',
            outputMimeType: 'application/json',
            systemPrompt: 'You assign blog categories. Return JSON only.',
            userPromptTemplate: 'Assign blog categories as JSON object {"categories": ["cat1", "cat2"]} for:\n\n{{ input.source_post.content.title }}\n{{ input.source_post.content.excerpt }}',
            responseMimeType: 'application/json',
        );

        $generateCover = new WorkflowNode(
            id: 'generate_cover_image',
            type: NodeType::ImageGeneration,
            name: 'Generate Cover Image',
        );
        $generateCover->addInputPort(new WorkflowPort(
            name: 'source_post',
            type: ArtifactType::File,
            allowedMimeTypes: ['application/json'],
        ));
        $generateCover->addOutputPort(new WorkflowPort(
            name: 'cover_image',
            type: ArtifactType::Image,
            allowedMimeTypes: ['image/png', 'image/jpeg', 'image/webp'],
        ));
        $generateCover->setSettings([
            'provider' => 'openai',
            'model' => 'gpt-image-1',
            'size' => '1024x1024',
            'format' => 'png',
            'inputPort' => 'source_post',
            'promptField' => 'excerpt',
            'outputAlias' => 'cover_image',
        ]);

        $finalOutput = new WorkflowNode(
            id: 'collect_site_post',
            type: NodeType::FinalOutput,
            name: 'Collect Site Post',
        );
        $finalOutput->addInputPort(new WorkflowPort(
            name: 'title',
            type: ArtifactType::File,
            required: true,
            allowedMimeTypes: ['text/plain'],
        ));
        $finalOutput->addInputPort(new WorkflowPort(
            name: 'description',
            type: ArtifactType::File,
            required: true,
            allowedMimeTypes: ['text/plain'],
        ));
        $finalOutput->addInputPort(new WorkflowPort(
            name: 'body',
            type: ArtifactType::File,
            required: true,
            allowedMimeTypes: ['text/plain'],
        ));
        $finalOutput->addInputPort(new WorkflowPort(
            name: 'tags',
            type: ArtifactType::File,
            required: true,
            allowedMimeTypes: ['application/json'],
        ));
        $finalOutput->addInputPort(new WorkflowPort(
            name: 'categories',
            type: ArtifactType::File,
            required: true,
            allowedMimeTypes: ['application/json'],
        ));
        $finalOutput->addInputPort(new WorkflowPort(
            name: 'cover_image',
            type: ArtifactType::Image,
            required: true,
            allowedMimeTypes: ['image/png'],
        ));
        $finalOutput->addOutputPort(new WorkflowPort(
            name: 'site_post',
            type: ArtifactType::File,
            allowedMimeTypes: ['application/json'],
        ));

        return [
            $input,
            $generateTitle,
            $generateDescription,
            $generateBody,
            $generateTags,
            $generateCategories,
            $generateCover,
            $finalOutput,
        ];
    }

    private static function promptNode(
        string $id,
        string $name,
        string $outputPort,
        string $outputMimeType,
        string $systemPrompt,
        string $userPromptTemplate,
        string $responseMimeType,
    ): WorkflowNode {
        $node = new WorkflowNode(
            id: $id,
            type: NodeType::Prompt,
            name: $name,
        );
        $node->addInputPort(new WorkflowPort(
            name: 'source_post',
            type: ArtifactType::File,
            allowedMimeTypes: ['application/json'],
        ));
        $node->addOutputPort(new WorkflowPort(
            name: $outputPort,
            type: ArtifactType::File,
            allowedMimeTypes: [$outputMimeType],
        ));
        $node->setSettings([
            'provider' => 'openai',
            'model' => 'gpt-4.1-mini',
            'systemPrompt' => $systemPrompt,
            'userPromptTemplate' => $userPromptTemplate,
            'responseMimeType' => $responseMimeType,
            'outputAlias' => $outputPort,
        ]);

        return $node;
    }

    /**
     * @return list<WorkflowEdge>
     */
    private static function edges(): array
    {
        $parallelTargets = [
            'generate_title',
            'generate_description',
            'generate_body',
            'generate_tags',
            'generate_categories',
            'generate_cover_image',
        ];

        $edges = [];

        foreach ($parallelTargets as $targetNodeId) {
            $edges[] = new WorkflowEdge(
                id: 'edge_source_to_' . $targetNodeId,
                fromNodeId: 'input_source_post',
                fromPort: 'source_post',
                toNodeId: $targetNodeId,
                toPort: 'source_post',
            );
        }

        $finalConnections = [
            ['generate_title', 'title', 'title'],
            ['generate_description', 'description', 'description'],
            ['generate_body', 'body', 'body'],
            ['generate_tags', 'tags', 'tags'],
            ['generate_categories', 'categories', 'categories'],
            ['generate_cover_image', 'cover_image', 'cover_image'],
        ];

        foreach ($finalConnections as [$fromNode, $fromPort, $toPort]) {
            $edges[] = new WorkflowEdge(
                id: sprintf('edge_%s_to_final', $fromNode),
                fromNodeId: $fromNode,
                fromPort: $fromPort,
                toNodeId: 'collect_site_post',
                toPort: $toPort,
            );
        }

        return $edges;
    }
}
