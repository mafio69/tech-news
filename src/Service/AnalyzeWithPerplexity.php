<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AnalyzeWithPerplexity
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $apiKey
    ) {}

    public function analyzeNews(string $title, string $url): array
    {
        $prompt = "Analyze this tech news: '$title' ($url). Return ONLY valid JSON: {\"summary\":\"1 sentence summary\",\"en\":\"English title\",\"pl\":\"Polish title\",\"es\":\"Spanish title\"}";

        try {
            $response = $this->httpClient->request('POST', 'https://api.perplexity.ai/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.1-sonar-huge-128k-online', // lub llama-3.1-sonar-small
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are tech news translator. Always respond with valid JSON only.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'max_tokens' => 300,
                ],
                'timeout' => 30.0
            ]);

            $status = $response->getStatusCode();
            $this->logger->debug('Perplexity HTTP response', ['url' => $url, 'status' => $status]);

            if ($status !== 200) {
                $this->logger->warning('Perplexity failed', ['url' => $url, 'status' => $status]);
                return [];
            }

            $content = $response->getContent(false);
            $data = json_decode($content, true);

            $this->logger->debug('Perplexity raw content', ['url' => $url, 'raw' => substr($content, 0, 500)]);

            $analysisText = $data['choices'][0]['message']['content'] ?? '{}';
            $analysis = json_decode($analysisText, true) ?: [];

            $this->logger->debug('Perplexity analysis result', ['url' => $url, 'analysis' => $analysis]);
            return $analysis;

        } catch (\Exception $e) {
            $this->logger->error('Perplexity error', ['url' => $url, 'error' => $e->getMessage()]);
            return [];
        }
    }
}
