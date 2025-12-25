<?php
// src/Service/AnalyzeService.php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class AnalyzeService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {}

    public function analyze(string $title, string $desc = '', string $url = ''): array
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

        $json = $this->callApi($prompt, $url);
        if ($json === null) {
            return null;
        }

        return $this->isValidJson($json) ? $json : null;
    }

    private function fallback(string $title): array
    {
        return [
            'summary' => substr($title, 0, 80),
            'en'      => $title,
            'pl'      => 'PL: ' . substr($title, 0, 50),
            'es'      => 'ES: ' . substr($title, 0, 50),
        ];
    }

    private function hasKey(): bool
    {
        return !empty($_ENV['PERPLEXITY_API_KEY']);
    }

    private function buildPrompt(string $title, string $desc): string
    {
        return "News title: \"$title\". "
            . "JSON: {\"summary\":\"PL\",\"en\":\"EN\",\"pl\":\"PL\",\"es\":\"ES\"}";
    }

    private function callApi(string $prompt, string $url): ?array
    {
        $apiKey = $_ENV['PERPLEXITY_API_KEY'] ?? null;

        try {
            $response = $this->httpClient->request(
                'POST',
                'https://api.perplexity.ai/chat/completions',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => [
                        'model'    => 'sonar',
                        'messages' => [
                            ['role' => 'system', 'content' => 'Return ONLY valid JSON.'],
                            ['role' => 'user',   'content' => $prompt],
                        ],
                        'max_tokens' => 100,
                    ],
                    'timeout' => 15.0,
                ]
            );

            if ($response->getStatusCode() !== 200) {
                $this->logger->warning('Perplexity failed', [
                    'url'    => $url,
                    'status' => $response->getStatusCode(),
                    'body'   => $response->getContent(false),
                ]);
                return null;
            }

            $data = json_decode($response->getContent(false), true);
            $raw  = $data['choices'][0]['message']['content'] ?? '{}';
            $analysis = json_decode($raw, true) ?: null;

            if (!is_array($analysis)) {
                return null;
            }

            return $analysis;
        } catch (\Throwable $e) {
            $this->logger->error('Perplexity error', [
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
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
