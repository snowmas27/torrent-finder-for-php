<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\ProviderResults;
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
        $results = new ProviderResults();
        $url = sprintf($this->providerInformation->getSearchUrl()->getUrl(), $keywords->urlize());
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filter('div.tgxtablerow') as $item) {
            $crawlerResultList = new Crawler($item);
            $cell = $crawlerResultList->filter('div.tgxtablecell');

            $metaData = TorrentData::fromMagnetURI(
                $title = $cell->eq(3)->text(),
                $cell->eq(4)->filter('a')->eq(1)->attr('href'),
                (int) $cell->eq(10)->filter('span > font')->text(),
                Resolution::guessFromString($title)
            );
            $results->add(new ProviderResult($this->providerInformation->getName(), $metaData, Size::fromHumanSize($cell->eq(7)->text())));
        }

        return $results->getResults();
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
