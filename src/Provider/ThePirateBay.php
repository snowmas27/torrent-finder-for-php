<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\ProviderResults;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\CrawlerInformationExtractor;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\SizeFactory;
use TorrentFinder\VideoSettings\Resolution;

class ThePirateBay implements Provider
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
        foreach ($crawler->filter('table#searchResult tr') as $item) {
            $itemCrawler = new Crawler($item);
            if (1 >= $itemCrawler->filter('td')->count()) {
                continue;
            }
            $title = trim($itemCrawler->filter('td')->eq(1)->filter('div.detName')->text());
            $magnet = $itemCrawler
                ->filter('td')
                ->eq(1)
                ->filter('a')
                ->eq(1)
                ->attr('href');
            preg_match(
                '/Size ([\.\w\s]+) (\w{2,3})/i',
                $itemCrawler->filter('font.detDesc')->text(),
                $match
            );

            if (empty($match[1])) {
                continue;
            }

            $results->add(new ProviderResult(
                $this->providerInformation->getName(),
                TorrentData::fromMagnetURI(
                    $title,
                    $magnet,
                    (int) $itemCrawler->filter('td')->eq(2)->text(),
                    Resolution::guessFromString($title)
                ),
                SizeFactory::convertFromWeirdFormat($match[1], $match[2])
            ));
        }

        return $results->getResults();
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
