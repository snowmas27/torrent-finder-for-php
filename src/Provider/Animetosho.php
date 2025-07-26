<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\ProviderResults;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\CrawlerInformationExtractor;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;
use TorrentFinder\VideoSettings\Resolution;

class Animetosho implements Provider
{
    use CrawlerInformationExtractor;
    private $providerInformation;

    public function __construct(ProviderInformation $providerInformation)
    {
        $this->providerInformation = $providerInformation;
    }

    public function search(SearchQueryBuilder $keywords): array
    {
        $results = new ProviderResults();
        $url = sprintf($this->providerInformation->getSearchUrl()->getUrl(), $keywords->urlize());
        $crawler = $this->initDomCrawler($url);

        foreach ($crawler->filter('item') as $item) {
            $itemCrawler = new Crawler($item);

            preg_match(
                '/<strong>Total Size<\/strong>: ([\.\w\s]+)/i',
                $itemCrawler->filter('description')->html(),
                $match
            );

            if (empty($match[1])) {
                continue;
            }

            $metaData = TorrentData::fromMagnetURI(
                $itemCrawler->filter('title')->text(),
                $itemCrawler->filter('enclosure')->attr('url'),
                10,
                Resolution::guessFromString($itemCrawler->filter('title')->text())
            );
            $results->add(new ProviderResult(
                ProviderType::provider($this->providerInformation->getName()),
                $metaData,
                Size::fromHumanSize($match[1])
            ));
        }

        return $results->getResults();
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
