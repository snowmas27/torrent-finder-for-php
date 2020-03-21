<?php

namespace App\Provider;

use Symfony\Component\DomCrawler\Crawler;
use App\Provider\ResultSet\ProviderResult;
use App\Provider\ResultSet\TorrentData;
use App\Search\ExtractContentFromUrlProvider;
use App\Search\SearchQueryBuilder;
use App\VideoSettings\Resolution;
use App\VideoSettings\Size;

class TorrentGalaxy implements Provider
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
