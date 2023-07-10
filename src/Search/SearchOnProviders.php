<?php

namespace TorrentFinder\Search;

use Assert\Assertion;
use TorrentFinder\Exception\Ensure;
use TorrentFinder\Provider\Jackett\JackettGenericSearch;
use TorrentFinder\Provider\Jackett\JackettIndexerList;
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

    /**
     * @param SearchQueryBuilder[] $queryBuilders
     * @param array $options
     */
    public function search(array $queryBuilders, array $options = []): SearchResults
    {
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
                        $results = $this->searchOnProvider(
                            $queryBuilder,
                            $providerConfiguration,
                            $forceRefresh
                        )
                    );
                } catch (\Exception $e) {
                    printf("%s\n", $e->getMessage());
                }
            }
            $searchResults = array_merge($searchResults, $this->jackettSearchOnIndexerList->searchAll($queryBuilder));
        }

        return new SearchResults($searchResults);
    }

    private function searchOnProvider(
        SearchQueryBuilder $queryBuilder,
        ProviderConfiguration $providerConfiguration,
        bool $forceRefresh
    ): array
    {
        if ($forceRefresh) {

            return $this->providerFactory->buildFromProviderConfiguration($providerConfiguration)->search($queryBuilder);
        }

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
