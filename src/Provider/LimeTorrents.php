<?php

namespace App\Provider;

use Symfony\Component\DomCrawler\Crawler;
use App\Provider\ResultSet\ProviderResult;
use App\Provider\ResultSet\TorrentData;
use App\Search\ExtractContentFromUrlProvider;
use App\Search\SearchQueryBuilder;
use App\VideoSettings\Size;
use App\VideoSettings\Resolution;

class LimeTorrents implements Provider
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
