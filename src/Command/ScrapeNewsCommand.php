<?php

namespace App\Command;

use AllowDynamicProperties;
use App\Service\AnalyzeService;
use App\Service\NewsScraperService;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AllowDynamicProperties]
#[AsCommand(
    name: 'app:scrape-news',
    description: 'Scrape tech news from RSS and analyze with OpenAI',
)]
class ScrapeNewsCommand extends Command
{
    public function __construct(NewsScraperService $scraper, LoggerInterface $logger)
    {
        parent::__construct();
        $this->scraper = $scraper;
        $this->logger = $logger;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limitEnv = (int)($_ENV['DAILY_NEWS_LIMIT'] ?? 20);

        try {
            $this->scraper->scrapeToday($limitEnv);
            $output->writeln(sprintf('📰 Scraping OK (limit=%d)', $limitEnv));

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            // log do Monologa
            $this->logger->error('Scrape failed', [
                'error' => $e->getMessage() . ' || ' .$e->getTraceAsString(),
            ]);

            // info w konsoli
            $output->writeln('<error>Scraping failed: '.$e->getMessage().'</error>');

            return Command::FAILURE;
        }
    }
}
