<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\CrawlerInformationExtractor;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Resolution;
use TorrentFinder\VideoSettings\Size;

class TorrentGalaxy implements Provider
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
        $url = sprintf($this->providerInformation->getSearchUrl()->getUrl(), $keywords->urlize());
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filter('div.tgxtablerow') as $item) {
            $crawlerResultList = new Crawler($item);
            $cell = $crawlerResultList->filter('div.tgxtablecell');

            $metaData = new TorrentData(
                $title = $cell->eq(3)->text(),
                $cell->eq(4)->filter('a')->eq(1)->attr('href'),
                (int) $cell->eq(10)->filter('span > font')->text(),
                Resolution::guessFromString($title)
            );
            $results[] = new ProviderResult($this->providerInformation->getName(), $metaData, Size::fromHumanSize($cell->eq(7)->text()));
        }

        return $results;
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
