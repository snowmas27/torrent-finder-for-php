<?php

namespace TorrentFinder\Search;

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

	public function search(SearchQueryBuilder $searchQueryBuilder): SearchResults
	{
		$searchResults = [];
		foreach ($this->providersName as $name) {
			$searchResults = array_merge($searchResults, ProviderFactory::buildFromName($name)->search($searchQueryBuilder));
		}
		$searchResults = new SearchResults($searchResults);

		return $searchResults;
	}
}