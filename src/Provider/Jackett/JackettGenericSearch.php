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
    const MAX_ITEMS_DEFAULT = 200;
    use CrawlerInformationExtractor;
    private string $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function search(array $options = [])
    {
        $results = new ProviderResults();
        $maxItems = $options['jackettMaxResults'] ?? self::MAX_ITEMS_DEFAULT;
        $crawler = $this->initDomCrawler($this->url);
        foreach ($crawler->filter('channel > item') as $item) {
            $crawlerResultList = new Crawler($item);
            $indexer = $this->findAttribute($crawlerResultList->filter('jackettindexer'), 'id');
            try {
                $title = $this->findText($crawlerResultList->filter('title'));
                $seeders = $this->findAttribute($crawlerResultList->filterXPath('//torznab:attr[@name="seeders"]'), 'value');
                $size = new Size((int) $this->findText($crawlerResultList->filter('size')));
                $magnet = $this->findMagnetUrl($crawlerResultList);

                if ($magnet !== null) {
                    $metaData = TorrentData::fromMagnetURI($title, $magnet, $seeders, Resolution::guessFromString($title));
                    $results->add(new ProviderResult($indexer, $metaData, $size));
                    continue;
                }

                $torrentUrl = $this->findTorrentUrl($crawlerResultList);
                if ($torrentUrl === null) {
                    continue;
                }
                $metaData = TorrentData::fromTorrentUrl($title, $torrentUrl, $seeders, Resolution::guessFromString($title));
                $results->add(new ProviderResult($indexer, $metaData, $size));
            } catch (\Exception $e) {
                printf('%s: %s', $indexer, $e->getMessage());
            }

            if (count($results->getResults()) >= $maxItems) {
                break;
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
    private function findTorrentUrl(Crawler $crawler): ?string
    {
        return $this->findText($crawler->filter('link'));
        // if ($url !== null) {
        //     return $url;
        // }
        // $urlCrawler = null !== $crawler->filter('comments') ? $crawler->filter('comments') : $crawler->filter('guid');
        // if ($urlCrawler === null) {
        //     return null;
        // }

        // $magnet = $this->findFirstMagnetUrl($this->findText($urlCrawler));

        // return $magnet;
    }
}
