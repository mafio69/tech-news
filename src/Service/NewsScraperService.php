<?php
// src/Service/NewsScraperService.php
namespace App\Service;

use App\Entity\News;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NewsScraperService
{
    public function __construct(
        private EntityManagerInterface $em,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private AnalyzeService $analyze
    ) {}

    public function scrapeToday(int $limit = 20): void
    {
        $news = $this->fetchUrls($limit);
        foreach ($news as $item) {
            try {
                $this->saveNews($item);
            } catch (\Throwable $e) {
                $this->logger->error('saveNews failed', [
                    'title' => substr($item['title'] ?? 'no-title', 0, 50),
                    'url' => $item['url'] ?? 'no-url',
                    'error' => $e->getMessage(),
                    'file' => $e->getFile() . ':' . $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                        }
        }
        $this->em->flush();
        $this->logger->info('Scraped', ['count' => count($news)]);
    }

    private function fetchUrls(int $limit): array
    {
        $urls = [
            "https://news.ycombinator.com/front?day=" . date('Y-m-d', strtotime('-2 days')),
            'https://techcrunch.com/',
            'https://arstechnica.com/tech-policy/',
        ];

        $allNews = [];

        foreach ($urls as $url) {
            foreach ($this->scrapeSite($url, (int) floor($limit / 3)) as $item) {

                $allNews[$item['url']] = $item;
            }
        }

        // mamy unikalne po URL, przytnij do limitu
        return array_slice(array_values($allNews), 0, $limit);
    }


    private function scrapeSite(string $url, int $max): array
    {
        try {
            $response = $this->httpClient->request('GET', $url, ['timeout' => 10]);
            return $this->parseSite(new Crawler($response->getContent(false)), $max);
        } catch (\Throwable $e) {
            $this->logger->warning('Site failed', ['url' => $url,
                'error' => $e->getMessage()]);
            return [];
        }
    }

    private function parseSite(Crawler $crawler, int $max): array
    {
        $news = match (true) {
            str_contains($crawler->filter('title')->text(), 'Hacker News')
            => $this->parseHackerNews($crawler),
            str_contains($crawler->filter('title')->text(), 'TechCrunch')
            => $this->parseTechCrunch($crawler),
            default => $this->parseArsTechnica($crawler)
        };
        return array_slice($news, 0, $max);
    }

    private function parseHackerNews(Crawler $crawler): array
    {
        $items = [];

        $crawler->filter('tr.athing')->each(function (Crawler $tr) use (&$items) {
            if (count($items) >= 20) {
                return;
            }

            $link = $tr->filter('.titleline a');
            if (!$link->count()) {
                return;
            }

            $title = trim($link->text());
            $url   = $link->attr('href');

            $items[] = [
                'title' => $title,
                'url'   => $url,
                'desc'  => '',
            ];
        });

        return $items;
    }

    private function parseTechCrunch(Crawler $crawler): array
    {
        $items = [];

        $crawler->filter('a.post-block__title__link')->each(function (Crawler $a) use (&$items) {
            if (count($items) >= 20) {
                return;
            }

            $title = trim($a->text());
            $url   = $a->attr('href');

            $items[] = [
                'title' => $title,
                'url'   => $url,
                'desc'  => '',
            ];
        });

        return $items;
    }

    private function parseArsTechnica(Crawler $crawler): array
    {
        $items = [];

        $crawler->filter('article')->each(function (Crawler $article) use (&$items) {
            if (count($items) >= 20) {
                return;
            }

            $link = $article->filter('h2 a');
            if (!$link->count()) {
                return;
            }

            $title = trim($link->text());
            $url   = $link->attr('href');

            $items[] = [
                'title' => $title,
                'url'   => $url,
                'desc'  => '',
            ];
        });

        return $items;
    }

    private function saveNews(array $item): void
    {
        if ($this->exists($item['url'])) return;

        $news = new News();
        $news->setTitle($item['title']);
        $news->setUrl($item['url']);
        $news->setCreatedAt(new \DateTimeImmutable('now'));
        $analysis = $this->analyze->analyze($item['title'], $item['desc'], $item['url']);

        // BEZPIECZNE setAnalysis - OMIJAMY PROBLEM
        if (is_array($analysis)) {
            $news->setAnalysis(json_encode($analysis, JSON_UNESCAPED_UNICODE));
        } else {
            $news->setAnalysis($analysis ?? '{}'); // też string
        }

        $this->em->persist($news);
        $this->logger->debug('Saved', ['title' => substr($item['title'], 0, 50)]);
    }

    private function exists(string $url): bool
    {
        return $this->em->getRepository(News::class)->findOneBy(['url' => $url]) !== null;
    }
}
