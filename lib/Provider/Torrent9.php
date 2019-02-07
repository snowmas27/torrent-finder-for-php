<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;
use TorrentFinder\VideoSettings\SizeFactory;
use TorrentFinder\VideoSettings\Resolution;

class Torrent9 implements Provider
{
    use ExtractContentFromUrlProvider;
    private $searchUrl;
    private $name;
    private $baseUrl;

    public function __construct()
    {
        $this->name = ProvidersAvailable::TORRENT9;
        $this->searchUrl = 'https://wvw.torrent9.uno/search_torrent/%s.html,trie-seeds-d';
        $this->baseUrl = 'https://wvw.torrent9.uno';
    }

    public function search(SearchQueryBuilder $keywords): array
    {
        $results = [];
        $url = sprintf($this->searchUrl, $keywords->urlize());
        /** @var Crawler $crawler */
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filter('table.cust-table')->filter('tr') as $item) {
            $itemCrawler = new Crawler($item);
            $detailPageCrawler = $this->initDomCrawler(
                $this->baseUrl . $itemCrawler->filter('a')->attr('href')
            );

            $magnet = null;
            foreach ($detailPageCrawler->filter('div.download-btn') as $itemDetailPage) {
                $itemCrawlerDetailPage = new Crawler($itemDetailPage);

                if (false === preg_match('/^magnet:\?/i', $itemCrawlerDetailPage->filter('a')->attr('href'))) {
                    continue;
                }
                $magnet = $itemCrawlerDetailPage->filter('a')->attr('href');
            }

            if (null === $magnet) {
                continue;
            }

            $results[] = new ProviderResult(
                $this->name,
                $metaData = new TorrentData(
                    $title = trim($itemCrawler->filter('a')->text()),
                    $magnet,
                    $itemCrawler->filter('.seed_ok')->count() === 1
                        ? (int) $itemCrawler->filter('.seed_ok')->text()
                        : 0
                    ,
                    Resolution::guessFromString($title)
                ),
                SizeFactory::fromHumanSize($itemCrawler->filter('td')->eq(1)->text())
            );
        }

        return $results;
    }
}
