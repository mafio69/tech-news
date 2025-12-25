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
        $apiKey = $_ENV['PERPLEXITY_API_KEY'] ?? null;

        try {
            // TU tworzysz obiekt $response
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
                            ['role' => 'system', 'content' => 'Return ONLY valid JSON, no markdown.'],
                            ['role' => 'user',   'content' => $prompt],
                        ],
                        'max_tokens' => 120,
                    ],
                    'timeout' => 15.0,
                ]
            );

            // STĄD korzystasz z tego samego $response
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

            $raw = trim($raw);
            $raw = preg_replace('/^``````$/i', '', $raw);

            $analysis = json_decode($raw, true);

            if (!is_array($analysis) || !$this->isValidJson($analysis)) {
                $this->logger->warning('Perplexity returned invalid JSON', [
                    'url'    => $url,
                    'raw'    => $raw,
                    'parsed' => $analysis,
                ]);
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
        return
            "You are a news assistant. ".
            "Summarize and translate the following tech news title and description.\n\n".
            "Title: \"$title\"\n".
            "Description: \"$desc\"\n\n".
            "Return ONLY valid JSON, with NO explanation, NO markdown.\n".
            "The JSON MUST have exactly these keys:\n".
            "{\n".
            "  \"summary\": \"Short summary in English (max 80 chars)\",\n".
            "  \"en\": \"English headline\",\n".
            "  \"pl\": \"Polish translation of the headline\",\n".
            "  \"es\": \"Spanish translation of the headline\"\n".
            "}";

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
