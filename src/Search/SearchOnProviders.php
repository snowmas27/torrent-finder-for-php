<?php

namespace TorrentFinder\Search;

use Assert\Assertion;
use TorrentFinder\Exception\Ensure;
use TorrentFinder\Provider\Jackett\JackettSearchOnIndexerList;
use TorrentFinder\Provider\ProviderConfiguration;
use TorrentFinder\Provider\ProviderFactory;
use TorrentFinder\Provider\ProvidersConfiguration;
use TorrentFinder\Provider\ResultSet\SearchResults;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class SearchOnProviders
{
    private $providersConfigurations;
    private $providerFactory;
    private $cache;
    private JackettSearchOnIndexerList $jackettSearchOnIndexerList;

    public function __construct(
        ProvidersConfiguration $providersConfigurations,
        ProviderFactory $providerFactory,
        JackettSearchOnIndexerList $jackettSearchOnIndexerList,
        CacheInterface $cache
    ) {
        $this->providersConfigurations = $providersConfigurations;
        $this->providerFactory = $providerFactory;
        $this->jackettSearchOnIndexerList = $jackettSearchOnIndexerList;
        $this->cache = $cache;
    }

    public function searchAll(array $queryBuilders, array $options = []): SearchResults
    {
        $results = new SearchResults([]);
        $results = $this->searchOnProviders($queryBuilders, $options);
        $jackettResults = $this->searchOnJackett($queryBuilders, $options);
        $results = array_merge($results->getResults(), $jackettResults->getResults());

        return new SearchResults($results);
    }

    public function searchOnProviders(
        array $queryBuilders,
        array $options = []
    ): SearchResults {
        Ensure::allIsInstanceOf($queryBuilders, SearchQueryBuilder::class);

        $forceRefresh = !empty($options['forceRefresh']) ? $options['forceRefresh'] : false;
        Assertion::boolean($forceRefresh);
        $searchResults = [];
        foreach ($queryBuilders as $queryBuilder) {
            foreach ($this->providersConfigurations->getConfiguration() as $providerConfiguration) {
                if (!empty($options['providers']) && !in_array($providerConfiguration->getInformation()->getName(), $options['providers'], true)) {
                    continue;
                }

                try {
                    $searchResults = array_merge(
                        $searchResults,
                        $this->providerFactory->buildFromProviderConfiguration($providerConfiguration)->search($queryBuilder)
                    );
                } catch (\Exception $e) {
                    printf("%s\n", $e->getMessage());
                }
            }
        }

        return new SearchResults($searchResults);
    }

    public function searchOnJackett(
        array $queryBuilders,
        array $options = []
    ): SearchResults {
        Ensure::allIsInstanceOf($queryBuilders, SearchQueryBuilder::class);

        $forceRefresh = !empty($options['forceRefresh']) ? $options['forceRefresh'] : false;
        Assertion::boolean($forceRefresh);
        $searchResults = [];
        foreach ($queryBuilders as $queryBuilder) {
            if (empty($options['jackett'])) {
                $searchResults = array_merge($searchResults, $this->jackettSearchOnIndexerList->searchAll($queryBuilder, $options));
                continue;
            }

            $searchResults = array_merge(
                $searchResults,
                $this->jackettSearchOnIndexerList->searchByIndexer($options['jackett'], $queryBuilder, $options)
            );
        }

        return new SearchResults($searchResults);
    }
}
