<?php

namespace App\Provider;

use Symfony\Component\DomCrawler\Crawler;
use App\Provider\ResultSet\ProviderResult;
use App\Provider\ResultSet\TorrentData;
use App\Search\ExtractContentFromUrlProvider;
use App\Search\SearchQueryBuilder;
use App\VideoSettings\SizeFactory;
use App\VideoSettings\Resolution;

class Extratorrent implements Provider
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
        foreach ($crawler->filterXPath('//table[@class=\'tl\']/tr[@class=\'tlr\']') as $item) {
            $domCrawler = new Crawler($item);
            list($value, $unit) = explode(' ', $domCrawler->filterXPath('//td[5]')->text());
            $size = SizeFactory::convertFromWeirdFormat($value, $unit);
            $seeds = $domCrawler->filterXPath('//td[6]')->text();
            $seeds = '---' === $seeds ? 0 : (int) $seeds;
            $title = trim($domCrawler->filterXPath('//td[3]/a[1]')->text());
            $magnet = trim($domCrawler->filter('a')->attr('href'));
            $metaData = new TorrentData($title, $magnet, $seeds, Resolution::guessFromString($title));
            $results[] = new ProviderResult($this->providerInformation->getName(), $metaData, $size);
        }

        return $results;
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
