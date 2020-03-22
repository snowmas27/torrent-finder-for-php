<?php

namespace TorrentFinder\Provider;

use TorrentFinder\Search\SearchQueryBuilder;

interface Provider
{
    public function getName(): string;
    public function search(SearchQueryBuilder $keywords): array;
}