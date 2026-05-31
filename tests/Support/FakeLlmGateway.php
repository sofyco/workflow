<?php declare(strict_types=1);

namespace Sofyco\Workflow\Tests\Support;

use Sofyco\Workflow\Application\Llm\LlmGatewayInterface;
use Sofyco\Workflow\Application\Llm\LlmRequest;
use Sofyco\Workflow\Application\Llm\LlmResponse;

final class FakeLlmGateway implements LlmGatewayInterface
{
    /** @var list<LlmRequest> */
    public array $requests = [];

    public function complete(LlmRequest $request): LlmResponse
    {
        $this->requests[] = $request;

        $userMessage = $this->extractUserMessage($request->messages);
        $systemMessage = $this->extractSystemMessage($request->messages);

        if (str_contains($userMessage, 'subtitle') || str_contains($userMessage, 'SRT')) {
            return new LlmResponse(
                rawContent: implode("\n", [
                    '1',
                    '00:00:00,000 --> 00:00:03,000',
                    'Discover the future of smart home automation.',
                    '',
                    '2',
                    '00:00:03,000 --> 00:00:07,000',
                    'Control lighting, climate, and security from one app.',
                ]),
            );
        }

        if (str_contains($userMessage, 'extract all posts') || str_contains($userMessage, 'Parse the website')) {
            return $this->websitePostsResponse($userMessage);
        }

        if (str_contains($userMessage, 'Generate a concise blog post title') || str_contains($systemMessage, 'blog post titles')) {
            return new LlmResponse(rawContent: 'PHP 8.5: What Developers Need to Know in 2026');
        }

        if (str_contains($userMessage, 'Generate a short meta description') || str_contains($systemMessage, 'meta descriptions')) {
            return new LlmResponse(rawContent: 'Explore the most impactful PHP 8.5 features and what they mean for your next project.');
        }

        if (str_contains($userMessage, 'Write a full blog post article') || str_contains($systemMessage, 'expand blog excerpts')) {
            return new LlmResponse(rawContent: <<<BODY
PHP 8.5 brings practical improvements for everyday development. From pipeline operators to smarter type inference, the release focuses on code clarity and performance.

In this article, we walk through the features that matter most for production applications and migration planning.

Whether you maintain legacy code or greenfield services, PHP 8.5 offers meaningful upgrades worth adopting this year.
BODY);
        }

        if (str_contains($userMessage, 'Generate SEO tags') || str_contains($systemMessage, 'SEO tags')) {
            $payload = ['tags' => ['php', 'php-8-5', 'backend', 'programming']];

            return new LlmResponse(
                rawContent: json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                parsedContent: $payload,
            );
        }

        if (str_contains($userMessage, 'Assign blog categories') || str_contains($systemMessage, 'blog categories')) {
            $payload = ['categories' => ['Development', 'PHP']];

            return new LlmResponse(
                rawContent: json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                parsedContent: $payload,
            );
        }

        if ($request->responseMimeType === 'application/json') {
            return new LlmResponse(
                rawContent: json_encode([
                    'title' => 'Smart Home Revolution',
                    'keywords' => ['smart home', 'automation', 'IoT'],
                ], JSON_THROW_ON_ERROR),
                parsedContent: [
                    'title' => 'Smart Home Revolution',
                    'keywords' => ['smart home', 'automation', 'IoT'],
                ],
            );
        }

        return new LlmResponse(
            rawContent: <<<SCRIPT
Welcome to the future of living. Imagine a home that anticipates your needs before you even ask.

Smart lighting adjusts to your mood. Climate control learns your schedule. Security keeps your family safe around the clock.

This is not science fiction. This is smart home automation, available today.
SCRIPT,
        );
    }

    private function websitePostsResponse(string $userMessage): LlmResponse
    {
        $sourceUrl = 'https://example.com/blog';
        $posts = [
            [
                'title' => 'PHP 8.5 Features Overview',
                'url' => 'https://example.com/blog/php-8-5-features',
                'publishedAt' => '2026-03-01',
                'excerpt' => 'A quick look at the most useful additions in PHP 8.5.',
            ],
            [
                'title' => 'Building Workflow Engines',
                'url' => 'https://example.com/blog/workflow-engines',
                'publishedAt' => '2026-02-15',
                'excerpt' => 'How to design artifact-based workflow graphs for multimodal pipelines.',
            ],
            [
                'title' => 'LLM-Assisted HTML Parsing',
                'url' => 'https://example.com/blog/llm-parsing',
                'publishedAt' => '2026-01-20',
                'excerpt' => 'Using prompts to extract structured data from messy page markup.',
            ],
        ];

        if (preg_match('#https?://[^\s]+#', $userMessage, $matches) === 1) {
            $sourceUrl = rtrim($matches[0], '.');
        }

        $payload = [
            'sourceUrl' => $sourceUrl,
            'posts' => $posts,
            'total' => count($posts),
        ];

        return new LlmResponse(
            rawContent: json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            parsedContent: $payload,
        );
    }

    /**
     * @param list<array<string, mixed>> $messages
     */
    private function extractUserMessage(array $messages): string
    {
        foreach ($messages as $message) {
            if (($message['role'] ?? '') === 'user' && is_string($message['content'] ?? null)) {
                return $message['content'];
            }
        }

        return '';
    }

    /**
     * @param list<array<string, mixed>> $messages
     */
    private function extractSystemMessage(array $messages): string
    {
        foreach ($messages as $message) {
            if (($message['role'] ?? '') === 'system' && is_string($message['content'] ?? null)) {
                return $message['content'];
            }
        }

        return '';
    }
}
