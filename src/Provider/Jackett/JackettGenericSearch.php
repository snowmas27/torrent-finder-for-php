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

    public function __construct(JackettUrlBuilder $url)
    {
        $this->url = $url;
    }

    public function search(SearchQueryBuilder $keywords)
    {
        $results = new ProviderResults();
        $url = sprintf($this->url->buildGenericUrl(), $keywords->urlize());
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filter('channel > item') as $item) {
            $crawlerResultList = new Crawler($item);
            $indexer = $this->findAttribute($crawlerResultList->filter('jackettindexer'), 'id');
            try {
                $title = $this->findText($crawlerResultList->filter('title'));
                $seeders = $this->findAttribute($crawlerResultList->filterXPath('//torznab:attr[@name="seeders"]'), 'value');
                $size = new Size((int) $this->findText($crawlerResultList->filter('size')));
                $magnet = $this->findMagnetUrl($crawlerResultList);
                if ($magnet === null) {
                    continue;
                }
                $metaData = new TorrentData($title, $magnet, $seeders, Resolution::guessFromString($title));
                $results->add(new ProviderResult($indexer, $metaData, $size));
            } catch (\Exception $e) {
                printf('%s: %s', $indexer, $e->getMessage());
            }
        }

        return $results->getResults();
    }

    private function findMagnetUrl(Crawler $crawler): ?string
    {
        $magnet = $this->findAttribute($crawler->filterXPath('//torznab:attr[@name="magneturl"]'), 'value');
        if ($magnet !== null) {
            return $magnet;
        }
        $urlCrawler = null !== $crawler->filter('comments') ? $crawler->filter('comments') : $crawler->filter('guid');
        if ($urlCrawler === null) {
            return null;
        }

        $magnet = $this->findFirstMagnetUrl($this->findText($urlCrawler));

        return $magnet;
    }
}
