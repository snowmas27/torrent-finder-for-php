<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\SizeFactory;
use TorrentFinder\VideoSettings\Resolution;

class Extratorrent implements Provider
{
    use ExtractContentFromUrlProvider;
    private $searchUrl;
    private $name;

    public function __construct()
    {
        $this->name = ProvidersAvailable::EXTRATORRENT;
        $this->searchUrl = 'https://extratorrents-cc.com/search/?mot_top=%s&new=1&x=0&y=0';
    }

    public function search(SearchQueryBuilder $keywords): array
    {
        $results = [];
        $url = sprintf($this->searchUrl, $keywords->urlize());
        /** @var Crawler $crawler */
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filterXPath('//table[@class=\'tl\']/tr[@class=\'tlr\']') as $item) {
            $domCrawler = new Crawler($item);
            list($value, $unit) = explode(' ', $domCrawler->filterXPath('//td[5]')->text());
            $size = SizeFactory::convertFromWeirdFormat($value, $unit);
            $seeds = $domCrawler->filterXPath('//td[6]')->text();
            $title = trim($domCrawler->filterXPath('//td[3]/a[1]')->text());
            $metaData = new TorrentData(
                $title,
                trim($domCrawler->filterXPath('//td[1]/a[2]/@href')->text()),
                $seeds,
                Resolution::guessFromString($title)
            );
            $results[] = new ProviderResult($this->name, $metaData, $size);
        }

        return $results;
    }
}
