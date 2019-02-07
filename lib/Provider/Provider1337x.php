<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;
use TorrentFinder\VideoSettings\Resolution;

class Provider1337x implements Provider
{
    use ExtractContentFromUrlProvider;

    private $searchUrl;
    private $name;
    private $baseUrl;

    public function __construct()
    {
        $this->name = ProvidersAvailable::PROVIDER1333X;
        $this->searchUrl = 'https://1337x.to/sort-search/%s/seeders/desc/1/';
        $this->baseUrl = 'https://1337x.to';
    }

    public function search(SearchQueryBuilder $keywords): array
    {
        $results = [];
        $url = sprintf($this->searchUrl, $keywords->urlize());
        $crawler = $this->initDomCrawler($url);

        foreach ($crawler->filter('div.table-list-wrap')->filter('tbody > tr') as $item) {
            $crawlerResultList = new Crawler($item);
            $td = $crawlerResultList->filter('td');

            $crawlerDetailPage = $this->initDomCrawler(
                sprintf(
                    '%s%s',
                    $this->baseUrl,
                    $td->eq(0)->filter('a')->eq(1)->attr('href')
                )
            );

            if (null === $magnet = $this->extractMagnet($crawlerDetailPage)) {
                continue;
            }

            $metaData = new TorrentData(
                $title = $td->eq(0)->filter('a')->eq(1)->text(),
                $magnet,
                $td->eq(1)->text(),
                Resolution::guessFromString($title)
            );

            $results[] = new ProviderResult(
                $this->name,
                $metaData,
                Size::fromHumanSize($td->eq(4)->text())
            );
        }

        return $results;
    }

    private function extractMagnet(Crawler $detailPage): ?string
    {
        foreach ($detailPage->filter('div.torrent-category-detail > ul > li > a') as $item) {
            $crawler = new Crawler($item);

            if (false === strpos($href = $crawler->attr('href'), 'magnet:')) {
                continue;
            }

            return $href;
        }

        return null;
    }
}
