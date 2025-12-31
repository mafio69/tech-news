<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AnalyzeService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {
    }

    public function analyze(string $title, string $desc, string $url): array
    {
        $result = $this->tryPerplexity($title, $desc, $url);

        return $result ?? $this->fallback($title);
    }

    private function tryPerplexity(string $title, string $desc, string $url): ?array
    {
        if (!$this->hasKey()) {
            return null;
        }

        $prompt = $this->buildPrompt($title, $desc);
        $apiKey = $_ENV['PERPLEXITY_API_KEY'] ?? null;

        try {
            $response = $this->httpClient->request(
                'POST',
                'https://api.perplexity.ai/chat/completions',
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => 'sonar',
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'Return ONLY valid JSON. No markdown, no explanations. Exact structure: {"summary":"","en":"","pl":"","es":""}',
                            ],
                            ['role' => 'user', 'content' => $prompt],
                        ],
                        'max_tokens' => 500,
                    ],
                    'timeout' => 15.0,
                ]
            );

            if ($response->getStatusCode() !== 200) {
                $this->logger->warning('Perplexity failed', [
                    'url' => $url,
                    'status' => $response->getStatusCode(),
                    'body' => $response->getContent(false),
                ]);

                return null;
            }

            $data = json_decode($response->getContent(false), true);
            $raw = trim($data['choices'][0]['message']['content'] ?? '{}');

            $raw = preg_replace('/```(?:json)?\s*/i', '', $raw);
            $analysis = json_decode($raw, true);

            if (!is_array($analysis) || !$this->isValidJson($analysis)) {
                $this->logger->warning('Perplexity returned invalid JSON', [
                    'url' => $url,
                    'raw' => substr($raw, 0, 200),
                ]);

                return null;
            }

            return $analysis;

        } catch (\Throwable $e) {
            $this->logger->error('Perplexity error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function fallback(string $title): array
    {
        $short = substr($title, 0, 100);

        return [
            'summary' => $short.'...',
            'en' => $short.'...',
            'pl' => 'PL: '.$short.'...',
            'es' => 'ES: '.$short.'...',
        ];
    }

    private function hasKey(): bool
    {
        return !empty($_ENV['PERPLEXITY_API_KEY']);
    }

    private function buildPrompt(string $title, string $desc): string
    {
        return <<<PROMPT
Analyze tech news. Return ONLY valid JSON, no markdown:

{
  "summary": "1-2 sentence summary in original language (max 110 chars)",
  "en": "English summary/translation (max 110 chars)",
  "pl": "Polish summary/translation (max 110 chars)",
  "es": "Spanish summary/translation (max 110 chars)"
}

TITLE: {$title}
DESCRIPTION: {$desc}

JSON ONLY:
PROMPT;
    }

    private function isValidJson(array $json): bool
    {
        return isset($json['summary'], $json['en'], $json['pl'], $json['es'])
            && is_string($json['summary'])
            && is_string($json['en'])
            && is_string($json['pl'])
            && is_string($json['es']);
    }
}
