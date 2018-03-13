<?php

namespace TorrentFinder\Provider;

use TorrentFinder\Provider\ResultSet\ProviderSearchResult;
use TorrentFinder\Search\SearchQueryBuilder;

interface Provider
{
	public function search(SearchQueryBuilder $keywords): ProviderSearchResult;
}