<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Resolution;
use TorrentFinder\VideoSettings\Size;

class Torlock implements Provider
{
    use ExtractContentFromUrlProvider;

    private $searchUrl;
    private $name;
    private $baseUrl;

    public function __construct()
    {
        $this->name = ProvidersAvailable::TORLOCK;
        $this->searchUrl = 'https://www.torlock2.com/all/torrents/%s.html?sort=seeds';
        $this->baseUrl = 'https://www.torlock2.com';
    }

    public function search(SearchQueryBuilder $keywords): array
    {
        $results = [];
        $url = sprintf($this->searchUrl, $keywords->urlize());
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filterXPath('//html/body/article/div[2]/table/tr') as $item) {
            $line = new Crawler($item);
            $cell = $line->filter('td');

            if (0 === $cell->eq(0)->count()) {
                continue;
            }

            $title = $cell->eq(0)->text();

            if (0 === $cell->eq(2)->count()) {
                continue;
            }
            $size = Size::fromHumanSize($cell->eq(2)->text());

            if (0 === $cell->eq(3)->count()) {
                continue;
            }

            $seeds = (int) $cell->eq(3)->text();

            $detailPage = $this->initDomCrawler(
                $this->baseUrl . $cell->eq(0)->filter('a')->attr('href')
            );


            if (null === $magnet = $this->extractMagnet($detailPage)) {
                continue;
            }

            $metaData = new TorrentData($title, $magnet, $seeds, Resolution::guessFromString($title));

            $results[] = new ProviderResult($this->name, $metaData, $size);
        }

        return $results;
    }

    private function extractMagnet(Crawler $detailPage): ?string
    {
        foreach ($detailPage->filterXPath('//html/body/article/table[2]') as $item) {
            $crawler = new Crawler($item);

            if (false === strpos($href = $crawler->filter('a')->attr('href'), 'magnet:')) {
                continue;
            }

            return $href;
        }

        return null;
    }

}
