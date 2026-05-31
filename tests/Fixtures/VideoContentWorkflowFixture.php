<?php declare(strict_types=1);

namespace Sofyco\Workflow\Tests\Fixtures;

use DateTimeImmutable;
use Sofyco\Workflow\Domain\Model\WorkflowEdge;
use Sofyco\Workflow\Domain\Model\WorkflowNode;
use Sofyco\Workflow\Domain\Model\WorkflowPort;
use Sofyco\Workflow\Domain\Model\WorkflowVersion;
use Sofyco\Workflow\Domain\Enum\ArtifactType;
use Sofyco\Workflow\Domain\Enum\NodeType;

final class VideoContentWorkflowFixture
{
    public const string WORKFLOW_ID = 'wf_video_content';
    public const string VERSION_ID = 'wfv_video_content_v1';

    public static function createVersion(): WorkflowVersion
    {
        $version = new WorkflowVersion(
            id: self::VERSION_ID,
            workflowId: self::WORKFLOW_ID,
            version: 1,
            name: 'Video Content Production',
            startNodeId: 'input_text',
            createdAt: new DateTimeImmutable(),
            publishedAt: new DateTimeImmutable(),
        );

        $version->setSettings([
            'maxTotalNodeExecutions' => 100,
            'maxAttemptsPerNode' => 5,
        ]);

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
            id: 'input_text',
            type: NodeType::Input,
            name: 'Input Text Description',
        );
        $input->addOutputPort(new WorkflowPort(
            name: 'source_description',
            type: ArtifactType::File,
            allowedMimeTypes: ['text/plain'],
        ));

        $generateScript = new WorkflowNode(
            id: 'generate_script',
            type: NodeType::Prompt,
            name: 'Generate Narration Script',
        );
        $generateScript->addInputPort(new WorkflowPort(
            name: 'story',
            type: ArtifactType::File,
            allowedMimeTypes: ['text/plain'],
        ));
        $generateScript->addOutputPort(new WorkflowPort(
            name: 'story',
            type: ArtifactType::File,
            allowedMimeTypes: ['text/plain'],
        ));
        $generateScript->setSettings([
            'provider' => 'openai',
            'model' => 'gpt-4.1-mini',
            'systemPrompt' => 'You write concise narration scripts for short-form video content.',
            'userPromptTemplate' => 'Expand this product description into a 3-paragraph narration script:\n\n{{ input.source_description.content }}',
            'responseMimeType' => 'text/plain',
            'outputAlias' => 'story',
        ]);

        $generateSubtitles = new WorkflowNode(
            id: 'generate_subtitles',
            type: NodeType::Prompt,
            name: 'Generate Subtitles',
        );
        $generateSubtitles->addInputPort(new WorkflowPort(
            name: 'story',
            type: ArtifactType::File,
            allowedMimeTypes: ['text/plain'],
        ));
        $generateSubtitles->addOutputPort(new WorkflowPort(
            name: 'subtitles',
            type: ArtifactType::File,
            allowedMimeTypes: ['text/plain'],
        ));
        $generateSubtitles->setSettings([
            'provider' => 'openai',
            'model' => 'gpt-4.1-mini',
            'systemPrompt' => 'You create SRT subtitle files for short videos.',
            'userPromptTemplate' => 'Create an SRT subtitle file for this narration script:\n\n{{ nodes.generate_script.latest.output.story.content }}',
            'responseMimeType' => 'text/plain',
            'outputAlias' => 'subtitles',
        ]);

        $voiceover = new WorkflowNode(
            id: 'elevenlabs_voiceover',
            type: NodeType::TextToSpeech,
            name: 'ElevenLabs Voiceover',
        );
        $voiceover->addInputPort(new WorkflowPort(
            name: 'script',
            type: ArtifactType::File,
            allowedMimeTypes: ['text/plain'],
        ));
        $voiceover->addOutputPort(new WorkflowPort(
            name: 'audio',
            type: ArtifactType::Audio,
            allowedMimeTypes: ['audio/mpeg'],
        ));
        $voiceover->setSettings([
            'provider' => 'elevenlabs',
            'model' => 'eleven_multilingual_v2',
            'voice' => 'Rachel',
            'format' => 'mp3',
            'inputPort' => 'script',
            'outputAlias' => 'audio',
        ]);

        $renderVideo = new WorkflowNode(
            id: 'render_video',
            type: NodeType::VideoRender,
            name: 'Render Video With Subtitles',
        );
        $renderVideo->addInputPort(new WorkflowPort(
            name: 'audio',
            type: ArtifactType::Audio,
            allowedMimeTypes: ['audio/mpeg'],
        ));
        $renderVideo->addInputPort(new WorkflowPort(
            name: 'subtitles',
            type: ArtifactType::File,
            allowedMimeTypes: ['text/plain'],
        ));
        $renderVideo->addOutputPort(new WorkflowPort(
            name: 'video',
            type: ArtifactType::Video,
            allowedMimeTypes: ['video/mp4'],
        ));
        $renderVideo->setSettings([
            'resolution' => '720x1280',
            'fps' => 30,
            'format' => 'mp4',
            'videoCodec' => 'libx264',
            'audioCodec' => 'aac',
            'outputAlias' => 'video',
        ]);

        $finalOutput = new WorkflowNode(
            id: 'collect_output',
            type: NodeType::FinalOutput,
            name: 'Collect Final Output',
        );
        $finalOutput->addInputPort(new WorkflowPort(
            name: 'video',
            type: ArtifactType::Video,
            required: true,
            allowedMimeTypes: ['video/mp4'],
        ));
        $finalOutput->addInputPort(new WorkflowPort(
            name: 'script',
            type: ArtifactType::File,
            required: true,
            allowedMimeTypes: ['text/plain'],
        ));
        $finalOutput->addInputPort(new WorkflowPort(
            name: 'audio',
            type: ArtifactType::Audio,
            required: true,
            allowedMimeTypes: ['audio/mpeg'],
        ));
        $finalOutput->addInputPort(new WorkflowPort(
            name: 'subtitles',
            type: ArtifactType::File,
            required: true,
            allowedMimeTypes: ['text/plain'],
        ));
        $finalOutput->addOutputPort(new WorkflowPort(
            name: 'result',
            type: ArtifactType::File,
            allowedMimeTypes: ['application/json'],
        ));

        return [
            $input,
            $generateScript,
            $generateSubtitles,
            $voiceover,
            $renderVideo,
            $finalOutput,
        ];
    }

    /**
     * @return list<WorkflowEdge>
     */
    private static function edges(): array
    {
        return [
            new WorkflowEdge(
                id: 'edge_input_to_script',
                fromNodeId: 'input_text',
                fromPort: 'source_description',
                toNodeId: 'generate_script',
                toPort: 'story',
            ),
            new WorkflowEdge(
                id: 'edge_script_to_subtitles',
                fromNodeId: 'generate_script',
                fromPort: 'story',
                toNodeId: 'generate_subtitles',
                toPort: 'story',
            ),
            new WorkflowEdge(
                id: 'edge_script_to_voiceover',
                fromNodeId: 'generate_script',
                fromPort: 'story',
                toNodeId: 'elevenlabs_voiceover',
                toPort: 'script',
            ),
            new WorkflowEdge(
                id: 'edge_subtitles_to_render',
                fromNodeId: 'generate_subtitles',
                fromPort: 'subtitles',
                toNodeId: 'render_video',
                toPort: 'subtitles',
            ),
            new WorkflowEdge(
                id: 'edge_audio_to_render',
                fromNodeId: 'elevenlabs_voiceover',
                fromPort: 'audio',
                toNodeId: 'render_video',
                toPort: 'audio',
            ),
            new WorkflowEdge(
                id: 'edge_video_to_final',
                fromNodeId: 'render_video',
                fromPort: 'video',
                toNodeId: 'collect_output',
                toPort: 'video',
            ),
            new WorkflowEdge(
                id: 'edge_script_to_final',
                fromNodeId: 'generate_script',
                fromPort: 'story',
                toNodeId: 'collect_output',
                toPort: 'script',
            ),
            new WorkflowEdge(
                id: 'edge_audio_to_final',
                fromNodeId: 'elevenlabs_voiceover',
                fromPort: 'audio',
                toNodeId: 'collect_output',
                toPort: 'audio',
            ),
            new WorkflowEdge(
                id: 'edge_subtitles_to_final',
                fromNodeId: 'generate_subtitles',
                fromPort: 'subtitles',
                toNodeId: 'collect_output',
                toPort: 'subtitles',
            ),
        ];
    }
}
