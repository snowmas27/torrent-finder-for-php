<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\SizeFactory;
use TorrentFinder\VideoSettings\Resolution;

class Eztv implements Provider
{
    use ExtractContentFromUrlProvider;
    private $searchUrl;
    private $name;

    public function __construct()
    {
        $this->name = ProvidersAvailable::EZTV;
        $this->searchUrl = 'https://eztv.io/search/%s';
    }

    public function search(SearchQueryBuilder $keywords): array
    {
        $results = [];
        $url = sprintf($this->searchUrl, $keywords->urlize('-'));
        /** @var Crawler $crawler */
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filter('table.forum_header_border tr.forum_header_border') as $item) {
            $domCrawler = new Crawler($item);
            $tds = $domCrawler->filter('td');
            if (6 !== $tds->count()) {
                continue;
            }

            $title = trim($tds->eq(1)->text());
            if (0 === $tds->eq(2)->filter('a')->count()) {
                continue;
            }
            $magnet = trim($tds->eq(2)->filter('a')->attr('href'));
            $size = SizeFactory::fromHumanSize(trim($tds->eq(3)->text()));
            $seeds = (int) trim($tds->eq(5)->text());
            $metaData = new TorrentData($title, $magnet, $seeds, Resolution::guessFromString($title));
            $results[] = new ProviderResult($this->name, $metaData, $size);
        }

        return $results;
    }
}
