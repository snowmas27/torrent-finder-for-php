<?php

namespace App\Provider;

use App\Search\SearchQueryBuilder;

interface Provider
{
    public function getName(): string;
    public function search(SearchQueryBuilder $keywords): array;
}