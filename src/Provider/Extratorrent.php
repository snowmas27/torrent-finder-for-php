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

class Extratorrent implements Provider
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
        /** @var Crawler $crawler */
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filterXPath('//table[@class=\'tl\']/tr[@class=\'tlr\']') as $item) {
            $domCrawler = new Crawler($item);
            list($value, $unit) = explode(' ', $domCrawler->filterXPath('//td[5]')->text());
            $size = SizeFactory::convertFromWeirdFormat($value, $unit);
            $seeds = $domCrawler->filterXPath('//td[6]')->text();
            $seeds = '---' === $seeds ? 0 : (int) $seeds;
            $title = trim($domCrawler->filterXPath('//td[3]/a[1]')->text());
            $magnet = trim($domCrawler->filter('a')->attr('href'));
            $metaData = TorrentData::fromMagnetURI($title, $magnet, $seeds, Resolution::guessFromString($title));
            $results->add(new ProviderResult($this->providerInformation->getName(), $metaData, $size));
        }

        return $results->getResults();
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
