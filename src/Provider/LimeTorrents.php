<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\CrawlerInformationExtractor;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;
use TorrentFinder\VideoSettings\Resolution;

class LimeTorrents implements Provider
{
    use CrawlerInformationExtractor;

    private $providerInformation;

    public function __construct(ProviderInformation $providerInformation)
    {
        $this->providerInformation = $providerInformation;
    }

    public function search(SearchQueryBuilder $keywords): array
    {
        $results = [];
        $url = sprintf($this->providerInformation->getSearchUrl()->getUrl(), $keywords->rawUrlEncode());
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
            $results[] = new ProviderResult($this->providerInformation->getName(), $metaData, $size);
        }
        return $results;
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
