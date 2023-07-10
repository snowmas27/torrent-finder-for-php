<?php

namespace TorrentFinder\Provider\Jackett;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\ProviderResults;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\CrawlerInformationExtractor;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Resolution;
use TorrentFinder\VideoSettings\Size;

class JackettGenericSearch
{
    use CrawlerInformationExtractor;
    private JackettUrlBuilder $url;
    private string $indexer;

    public function __construct(JackettUrlBuilder $url, string $indexer)
    {
        $this->url = $url;
        $this->indexer = $indexer;
    }

    public function search(SearchQueryBuilder $keywords)
    {
        $results = new ProviderResults();
        $url = sprintf($this->url->buildGenericUrl(), $this->indexer, $keywords->urlize());
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filter('channel > item') as $item) {
            $crawlerResultList = new Crawler($item);
            $title = $this->findText($crawlerResultList->filter('title'));
            $seeders = $this->findAttribute($crawlerResultList->filterXPath('//torznab:attr[@name="seeders"]'), 'value');
            $size = new Size((int) $this->findText($crawlerResultList->filter('size')));
            $magnet = $this->findAttribute($crawlerResultList->filterXPath('//torznab:attr[@name="magneturl"]'), 'value');
            $metaData = new TorrentData($title, $magnet, $seeders, Resolution::guessFromString($title));
            $results->add(new ProviderResult($this->indexer, $metaData, $size));
        }

        return $results->getResults();
    }
}
