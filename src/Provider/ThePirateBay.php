<?php

namespace App\Provider;

use Symfony\Component\DomCrawler\Crawler;
use App\Provider\ResultSet\ProviderResult;
use App\Provider\ResultSet\TorrentData;
use App\Search\ExtractContentFromUrlProvider;
use App\Search\SearchQueryBuilder;
use App\VideoSettings\SizeFactory;
use App\VideoSettings\Resolution;

class ThePirateBay implements Provider
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
                ->attr('href')
            ;
            preg_match(
                '/Size ([\.\w\s]+) (\w{2,3})/i',
                $itemCrawler->filter('font.detDesc')->text(),
                $match
            );

            if (empty($match[1])) {
                continue;
            }

            $results[] = new ProviderResult(
                $this->providerInformation->getName(),
                new TorrentData(
                    $title,
                    $magnet,
                    (int) $itemCrawler->filter('td')->eq(2)->text(),
                    Resolution::guessFromString($title)
                ),
                SizeFactory::convertFromWeirdFormat($match[1], $match[2])
            );
        }

        return $results;
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
