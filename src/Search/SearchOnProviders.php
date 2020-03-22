<?php

namespace TorrentFinder\Search;

use TorrentFinder\Exception\Ensure;
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

    public function __construct(
        ProvidersConfiguration $providersConfigurations,
        ProviderFactory $providerFactory,
        CacheInterface $cache
    ) {
        $this->providersConfigurations = $providersConfigurations;
        $this->providerFactory = $providerFactory;
        $this->cache = $cache;
    }

    /**
     * @param SearchQueryBuilder[] $queryBuilders
     */
    public function search(array $queryBuilders, array $onlyOnProviders = []): SearchResults
    {
        Ensure::allIsInstanceOf($queryBuilders, SearchQueryBuilder::class);

        $searchResults = [];
        foreach ($queryBuilders as $queryBuilder) {
            foreach ($this->providersConfigurations->getConfiguration() as $providerConfiguration) {
                if (!empty($onlyOnProviders) && !in_array($providerConfiguration->getInformation()->getName(), $onlyOnProviders, true)) {
                    continue;
                }

                try {
                    $searchResults = array_merge(
                        $searchResults,
                        $results = $this->searchOnProvider($queryBuilder, $providerConfiguration)
                    );
                } catch (\Exception $e) {
                    printf("%s\n", $e->getMessage());
                }
            }
        }
        $searchResults = new SearchResults($searchResults);

        return $searchResults;
    }

    private function searchOnProvider(SearchQueryBuilder $queryBuilder, ProviderConfiguration $providerConfiguration): array
    {
        return $this->cache->get(
            sprintf('%s-%s', $queryBuilder->urlize('-'), $providerConfiguration->getInformation()->getName()),
            function (ItemInterface $item) use ($queryBuilder, $providerConfiguration) {
                // Expire every 12h
                $item->expiresAfter(12 * 60 * 60);

                return $this->providerFactory->buildFromProviderConfiguration($providerConfiguration)->search($queryBuilder);
            }
        );
    }
}
