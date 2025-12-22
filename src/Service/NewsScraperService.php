<?php
// src/Service/NewsScraperService.php
namespace App\Service;

use App\Entity\News;
use Doctrine\ORM\EntityManagerInterface;
use SimplePie\SimplePie;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class NewsScraperService
{


    /** @var string[] */
    private array $rssFeeds = [
        'https://hackernewsrss.com/feed.xml',
        'https://techcrunch.com/feed/',
        'http://feeds.arstechnica.com/arstechnica/index',
        'https://www.theverge.com/rss/index.xml',
        'https://gizmodo.com/rss',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {}


    public function scrapeAndAnalyze(int $dailyLimit = 20): int
    {
        $this->logger->info('TEST LOGGER', ['time' => new \DateTimeImmutable()->format(DATE_ATOM)]);

        $existingUrls = $this->getExistingUrls();
        $createdToday  = $this->countTodayNews();
        $remaining     = max(0, $dailyLimit - $createdToday);

        $this->logger->info('Scraper start', [
            'daily_limit'   => $dailyLimit,
            'created_today' => $createdToday,
            'remaining'     => $remaining,
        ]);

        if ($remaining === 0) {
            return 0;
        }

        $newNewsCount = 0;

        foreach ($this->rssFeeds as $feedUrl) {
            if ($newNewsCount >= $remaining) {
                break;
            }
            $this->logger->info('Fetching RSS feed', ['feed' => $feedUrl]);
            $feed = new SimplePie();
            $feed->set_feed_url($feedUrl);

            // cache poza projektem, bez problemu z uprawnieniami
            $cacheDir = '/tmp/simplepie_' . md5($feedUrl);
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0755, true);
            }
            $feed->set_cache_location($cacheDir);
            $feed->set_cache_duration(3600);

            $feed->init();

            foreach ($feed->get_items(0, 10) as $item) {
                if ($newNewsCount >= $remaining) {
                    break;
                }

                $url = (string) $item->get_permalink();
                if (in_array($url, $existingUrls, true)) {
                    continue;
                }

                $title       = (string) ($item->get_title() ?? 'No title');
                $description = (string) ($item->get_description() ?? '');

                $this->logger->info('Analyzing news', [
                    'url'   => $url,
                    'title' => $title,
                ]);

                $news = new News();
                $news->setTitle($title);
                $news->setUrl($url);

                // TU tworzymy $analysis, a potem ustawiamy
                $analysis = $this->analyzeWithPerplexity($title, $description, $url);
                $news->setAnalysis($analysis); // ARRAY – Doctrine JSON zrobi resztę
                $this->logger->debug('OpenAI analysis result', [
                    'url'      => $url,
                    'analysis' => $analysis,
                ]);
                $news->setCreatedAt(new \DateTimeImmutable());
                $this->em->persist($news);

                $existingUrls[] = $url;
                $newNewsCount++;
            }
        }

        if ($newNewsCount > 0) {
            $this->em->flush();
        }

        $this->logger->info('Scraper finished', [
            'added' => $newNewsCount,
        ]);

        return $newNewsCount;
    }


    private function getExistingUrls(): array
    {
        $results = $this->em->createQueryBuilder()
            ->select('n.url')
            ->from(News::class, 'n')
            ->getQuery()
            ->getSingleColumnResult();

        return array_map('strval', $results ?: []);
    }

    private function countTodayNews(): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(n.id)')
            ->from(News::class, 'n')
            ->where('n.createdAt >= :today')
            ->setParameter('today', (new \DateTimeImmutable('today'))->setTime(0, 0, 0))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Zwraca tablicę:
     * [
     *   'summary' => 'PL podsumowanie',
     *   'en'      => 'EN title + summary',
     *   'pl'      => 'PL title + summary',
     *   'es'      => 'ES title + summary',
     * ]
     */
    private function analyzeWithPerplexity(string $title, string $description, string $url): array
    {
        // 1. Symfony ENV (nie $_ENV!)
        $apiKey = $_ENV['PERPLEXITY_API_KEY'] ?? null;
        if (empty($apiKey)) {
            $this->logger->warning('Perplexity key missing', ['url' => $url]);
            return [
                'summary' => 'Brak klucza Perplexity',
                'en' => $title,
                'pl' => $title,
                'es' => $title,
            ];
        }

        $prompt = "Analyze: '$title' ($url). Return ONLY valid JSON {\"summary\":\"PL summary\",\"en\":\"EN title\",\"pl\":\"PL title\",\"es\":\"ES title\"}";

        try {
            $response = $this->httpClient->request('POST', 'https://api.perplexity.ai/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.1-sonar-small-online',  // ✅ POPRAWNY
                    'messages' => [  // ✅ SYSTEM + USER
                        ['role' => 'system', 'content' => 'Respond ONLY with valid JSON. No other text.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'max_tokens' => 300,
                    'temperature' => 0.1,
                ],
                'timeout' => 30.0
            ]);

            $status = $response->getStatusCode();
            $this->logger->debug('Perplexity HTTP', ['url' => $url, 'status' => $status]);

            if ($status !== 200) {
                $this->logger->warning('Perplexity failed', ['url' => $url, 'status' => $status]);
                return [];
            }

            $data = json_decode($response->getContent(false), true);
            $raw = $data['choices'][0]['message']['content'] ?? '{}';
            $analysis = json_decode($raw, true) ?: [];

            $this->logger->debug('Perplexity result', ['url' => $url, 'analysis' => $analysis]);
            return $analysis;

        } catch (\Throwable $e) {
            $this->logger->error('Perplexity error', ['url' => $url, 'error' => $e->getMessage()]);
            return [];
        }
    }



}
