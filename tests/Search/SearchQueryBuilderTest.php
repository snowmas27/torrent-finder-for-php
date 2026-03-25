<?php

namespace TorrentFinder\Tests\Search;

use PHPUnit\Framework\TestCase;
use TorrentFinder\Search\SearchQuery;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Resolution;

class SearchQueryBuilderTest extends TestCase
{
    private function makeBuilder(string $query, Resolution $resolution): SearchQueryBuilder
    {
        return new SearchQueryBuilder(new SearchQuery($query), $resolution);
    }

    public function testGetQuery(): void
    {
        $builder = $this->makeBuilder('Inception 2010', Resolution::hd());

        $this->assertSame('Inception 2010', $builder->getQuery());
    }

    public function testGetQueryWithMovieYear(): void
    {
        $query = SearchQuery::movie('Inception', 2010);
        $builder = new SearchQueryBuilder($query, Resolution::hd());

        $this->assertSame('Inception 2010', $builder->getQuery());
    }

    public function testGetFormat(): void
    {
        $builder = $this->makeBuilder('Some Movie', Resolution::fullHd());

        $this->assertSame(Resolution::FULL_HD, $builder->getFormat()->getValue());
    }

    public function testGetSearchKeywordsIncludesResolution(): void
    {
        $builder = $this->makeBuilder('Some Movie', Resolution::hd());

        $this->assertSame('Some Movie 720p', $builder->getSearchKeywords());
    }

    public function testGetSearchKeywordsWithLdResolutionOmitsResolution(): void
    {
        $builder = $this->makeBuilder('Some Movie', Resolution::ld());

        $this->assertSame('Some Movie', $builder->getSearchKeywords());
    }

    public function testGetSearchKeywordsWithCustomKeywords(): void
    {
        $builder = $this->makeBuilder('Some Movie', Resolution::hd());
        $builder->addKeywords('FRENCH');

        $this->assertSame('Some Movie 720p FRENCH', $builder->getSearchKeywords());
    }

    public function testGetSearchKeywordsWithoutResolution(): void
    {
        $builder = $this->makeBuilder('Some Movie', Resolution::fullHd());

        $this->assertSame('Some Movie', $builder->getSearchKeywordsWithoutResolution());
    }

    public function testUrlizeDefaultChar(): void
    {
        $builder = $this->makeBuilder('Some Movie', Resolution::hd());

        $this->assertSame('Some+Movie+720p', $builder->urlize());
    }

    public function testUrlizeCustomChar(): void
    {
        $builder = $this->makeBuilder('Some Movie', Resolution::hd());

        $this->assertSame('Some-Movie-720p', $builder->urlize('-'));
    }

    public function testUrlEncodeWithResolution(): void
    {
        $builder = $this->makeBuilder('Some Movie', Resolution::hd());

        $this->assertSame('Some+Movie+720p', $builder->urlEncode(true));
    }

    public function testUrlEncodeWithoutResolution(): void
    {
        $builder = $this->makeBuilder('Some Movie', Resolution::hd());

        $this->assertSame('Some+Movie', $builder->urlEncode(false));
    }

    public function testRawUrlEncode(): void
    {
        $builder = $this->makeBuilder('Some Movie', Resolution::hd());

        $this->assertSame('Some%20Movie%20720p', $builder->rawUrlEncode());
    }

    public function testGetResolution(): void
    {
        $builder = $this->makeBuilder('Some Movie', Resolution::ultraHd());

        $this->assertSame(Resolution::ULTRA_HD, $builder->getResolution()->getValue());
    }

    public function testTvShowEpisodeQuery(): void
    {
        $query = SearchQuery::tvShowEpisode('Breaking Bad', 3, 7);
        $builder = new SearchQueryBuilder($query, Resolution::fullHd());

        $this->assertSame('Breaking Bad S03E07', $builder->getQuery());
        $this->assertSame('Breaking Bad S03E07 1080p', $builder->getSearchKeywords());
    }

    public function testAddKeywordsDoesNotAffectSearchKeywordsWithoutResolution(): void
    {
        $builder = $this->makeBuilder('Some Movie', Resolution::hd());
        $builder->addKeywords('VOSTFR');

        $this->assertSame('Some Movie', $builder->getSearchKeywordsWithoutResolution());
    }
}
