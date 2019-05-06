<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\ProviderSearchResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;
use TorrentFinder\VideoSettings\Resolution;

class LimeTorrents implements Provider
{
    use ExtractContentFromUrlProvider;
    private $searchUrl;
    private $name;

    public function __construct()
    {
        $this->name = ProvidersAvailable::LIMETORRENTS;
        $this->searchUrl = 'https://lime1.unblocked.lol/searchrss/%s/';
    }

    public function search(SearchQueryBuilder $keywords): array
    {
        $results = [];
        $url = sprintf($this->searchUrl, $keywords->rawUrlEncode());
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filter('channel > item') as $item) {
            $crawlerResultList = new Crawler($item);
            $title = $crawlerResultList->filter('title')->text();
            preg_match(
                '/Seeds: (\d+)/i',
                $crawlerResultList->filter('description')->text(),
                $match
            );
            $currentSeeds = $match[1] ?? 0;
            $size = new Size((int) $crawlerResultList->filter('size')->text());
            $metaData = new TorrentData(
                $title,
                (string) $crawlerResultList->filterXPath('//enclosure/@url')->text(),
                $currentSeeds,
                Resolution::guessFromString($title)
            );
            $results[] = new ProviderResult($this->name, $metaData, $size);
        }
        return $results;
    }
}
