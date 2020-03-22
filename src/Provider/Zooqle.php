<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;
use TorrentFinder\VideoSettings\Resolution;

class Zooqle implements Provider
{
    use ExtractContentFromUrlProvider;

    private $providerInformation;

    public function __construct(ProviderInformation $providerInformation)
    {
        $this->providerInformation = $providerInformation;
    }

    public function search(SearchQueryBuilder $keywords): array
    {
        $results = [];
        $url = sprintf($this->providerInformation->getSearchUrl()->getUrl(), $keywords->urlize());
        /** @var Crawler $crawler */
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filter('item') as $item) {
            $domCrawler = new Crawler($item);
            $title = $domCrawler->filter('title')->text();
            $length = $domCrawler->filterXPath('//torrent:contentLength')->text();
            $seeds = $domCrawler->filterXPath('//torrent:seeds')->text();
            $size = new Size((int) $length);
            $metaData = new TorrentData(
                $title,
                $domCrawler->filterXPath('//torrent:magnetURI')->text(),
                $seeds,
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
