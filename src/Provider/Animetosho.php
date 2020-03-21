<?php

namespace App\Provider;

use Symfony\Component\DomCrawler\Crawler;
use App\Provider\ResultSet\ProviderResult;
use App\Provider\ResultSet\TorrentData;
use App\Search\ExtractContentFromUrlProvider;
use App\Search\SearchQueryBuilder;
use App\VideoSettings\Size;
use App\VideoSettings\Resolution;

class Animetosho implements Provider
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

            $metaData = new TorrentData(
                $itemCrawler->filter('title')->text(),
                $itemCrawler->filter('enclosure')->attr('url'),
                10,
                Resolution::guessFromString($itemCrawler->filter('title')->text())
            );
            $results[] = new ProviderResult($this->providerInformation->getName(), $metaData, Size::fromHumanSize($match[1]));
        }

        return $results;
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
