<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Resolution;
use TorrentFinder\VideoSettings\Size;

class TorrentGalaxy implements Provider
{
    use ExtractContentFromUrlProvider;
    private $searchUrl;
    private $name;

    public function __construct()
    {
        $this->name = ProvidersAvailable::TORRENTGALAXY;
        $this->searchUrl = 'https://torrentgalaxy.org/torrents.php?search=%s&sort=seeders&order=desc';
    }

    public function search(SearchQueryBuilder $keywords): array
    {
        $results = [];
        $url = sprintf($this->searchUrl, $keywords->urlize());
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filterXPath('//*[@id="click"]') as $item) {
            $crawlerResultList = new Crawler($item);
            $cell = $crawlerResultList->filter('div.tgxtablecell');
            if (0 === $cell->eq(3)->count()) {
                continue;
            }
            $metaData = new TorrentData(
                $title = $cell->eq(3)->filter('a')->text(),
                $cell->eq(4)->filter('a')->eq(1)->attr('href'),
                (int) $cell->eq(10)->filter('span > font')->eq(0)->text(),
                Resolution::guessFromString($title)
            );
            $results[] = new ProviderResult($this->name, $metaData, Size::fromHumanSize($cell->eq(7)->text()));
        }

        return $results;
    }

}
