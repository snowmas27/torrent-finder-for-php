<?php

namespace App\Provider;

use Symfony\Component\DomCrawler\Crawler;
use App\Provider\ResultSet\ProviderResult;
use App\Provider\ResultSet\TorrentData;
use App\Search\ExtractContentFromUrlProvider;
use App\Search\SearchQueryBuilder;
use App\VideoSettings\SizeFactory;
use App\VideoSettings\Resolution;

class Nyaa implements Provider
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
        /** @var \DOMElement $domElement */
        foreach ($crawler->filter('table.torrent-list')->filter('tr.default') as $item) {
            $itemCrawler = new Crawler($item);
            $td = $itemCrawler->filter('td');
            $index = 2 === $td->eq(1)->filter('a')->count() ? 1 : 0;
            list($size, $unit) = explode(' ', $td->eq(3)->text());
            $size = SizeFactory::convertFromWeirdFormat($size, $unit);
            $metaData = new TorrentData(
                $title = trim($td->eq(1)->filter('a')->eq($index)->text()),
                $td->eq(2)->filter('a')->eq(1)->attr('href'),
                $td->eq(5)->text(),
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
