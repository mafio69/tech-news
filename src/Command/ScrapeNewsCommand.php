<?php

namespace App\Command;

use App\Service\NewsScraperService;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:scrape-news',
    description: 'Scrape tech news from RSS and analyze with OpenAI',
)]
class ScrapeNewsCommand extends Command
{
    public function __construct(
        private readonly NewsScraperService $scraper,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        file_put_contents(
            __DIR__ . '/../../var/test-scrape.txt',
            (new \DateTimeImmutable())->format(DATE_ATOM) . " app:scrape-news\n",
            FILE_APPEND
        );
        $io       = new SymfonyStyle($input, $output);
        $limitEnv = (int) ($_ENV['DAILY_NEWS_LIMIT'] ?? 20);

        $io->title(sprintf('📰 Tech News Scraper (%d/dzień)', $limitEnv));

        $start = microtime(true);

        // log start
        $this->logger->info('app:scrape-news start', [
            'limit'     => $limitEnv,
            'timestamp' => new DateTimeImmutable()->format(DATE_ATOM),
        ]);

        $added   = $this->scraper->scrapeAndAnalyze($limitEnv);
        $timeSec = round(microtime(true) - $start, 1);

        // log result
        $this->logger->info('app:scrape-news finished', context: [
            'added'     => $added,
            'duration'  => $timeSec,
            'timestamp' => new DateTimeImmutable()->format(DATE_ATOM),
        ]);

        $io->success(sprintf('Dodano %d nowych newsów w %s s', $added, $timeSec));

        return Command::SUCCESS;
    }
}
