<?php

namespace TorrentFinder\Command;

use TorrentFinder\Search\SearchOnProviders;
use TorrentFinder\Search\SearchQuery;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Resolution;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ManualSearchCommand extends Command
{
    private $searchOnProviders;

    public function __construct(SearchOnProviders $searchOnProviders)
    {
        parent::__construct();
        $this->searchOnProviders = $searchOnProviders;
    }

    protected function configure(): void
    {
        $this
            ->setName('search:manual')
            ->addArgument('query', InputArgument::REQUIRED, 'Query to search')
            ->addArgument('resolution', InputArgument::OPTIONAL, 'Resolution (2160p|1080p|720p)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $resolution = $input->getArgument('resolution') ?
            new Resolution($input->getArgument('resolution'))
            : Resolution::ld()
        ;

        $results = $this->searchOnProviders->search([
                new SearchQueryBuilder(new SearchQuery($input->getArgument('query')), $resolution)
            ]
        );

        foreach ($results->getResults() as $result) {
            $output->writeln(json_encode($result->getTorrentMetaData()->toArray()));
        }

        return 1;
    }
}