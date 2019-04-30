<?php

namespace TorrentFinder\Search;

use TorrentFinder\Exception\Ensure;
use TorrentFinder\Provider\ProviderFactory;
use TorrentFinder\Provider\ProvidersAvailable;
use TorrentFinder\Provider\ResultSet\SearchResults;

class SearchOnProviders
{
    private $providersName;

    public static function all(): self
    {
        return new static(ProvidersAvailable::getList());
    }

    public static function specificProviders(array $providersName): self
    {
        return new static($providersName);
    }

    private function __construct(array $providersName)
    {
        $this->providersName = $providersName;
    }

    /**
     * @param SearchQueryBuilder[] $queryBuilders
     */
    public function search(array $queryBuilders): SearchResults
    {
        Ensure::allIsInstanceOf($queryBuilders, SearchQueryBuilder::class);
        $searchResults = [];
        foreach ($queryBuilders as $queryBuilder) {
            foreach ($this->providersName as $name) {
                try {
                    $searchResults = array_merge($searchResults, $results = ProviderFactory::buildFromName($name)->search($queryBuilder));
                } catch (\Exception $e) {
                    printf("%s\n", $e->getMessage());
                }
            }
        }
        $searchResults = new SearchResults($searchResults);

        return $searchResults;
    }
}
