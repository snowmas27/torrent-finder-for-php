<?php

namespace App\Provider;

use Symfony\Component\DomCrawler\Crawler;
use App\Provider\ResultSet\ProviderResult;
use App\Provider\ResultSet\TorrentData;
use App\Search\ExtractContentFromUrlProvider;
use App\Search\SearchQueryBuilder;
use App\VideoSettings\Size;
use App\VideoSettings\Resolution;

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
