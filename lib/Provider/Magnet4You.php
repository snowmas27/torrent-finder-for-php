<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\ProviderSearchResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;
use TorrentFinder\VideoSettings\Resolution;

class Magnet4You implements Provider
{
    use ExtractContentFromUrlProvider;
    private $searchUrl;
    private $name;

    public function __construct()
    {
        $this->name = ProvidersAvailable::MAGNET4YOU;
        $this->searchUrl = 'https://magnet4you.me/search.php?s=%s&sort=seed';
    }

    public function search(SearchQueryBuilder $keywords): array
    {
        $results = [];
        $url = sprintf($this->searchUrl, $keywords->urlize());
        try {
            $crawler = $this->initDomCrawler($url);
            /** @var \DOMElement $domElement */
            foreach ($crawler->filterXPath("//table[@class='tb4']/tr") as $domElement) {
                $crawlerListResult = new Crawler($domElement);

                $title = trim($crawlerListResult->filterXPath('//td[1]/a[2]')->text());
                $magnet = $crawlerListResult->filterXPath('//td[1]/a[1]/@href')->text();
                $humanSize = trim($crawlerListResult->filterXPath('//td[3]')->text());
                try {
                    $size = Size::fromHumanSize($humanSize);
                } catch (\UnexpectedValueException $e) {
                    continue;
                }
                $currentSeeds = trim($crawlerListResult->filterXPath('//td[5]')->text());
                $metaData = new TorrentData(
                    trim($title),
                    $magnet,
                    $currentSeeds,
                    Resolution::guessFromString($title)
                );
                $results[] = new ProviderResult($this->name, $metaData, $size);
            }
        } catch (\UnexpectedValueException $e) {
        } catch (\InvalidArgumentException $e) {
        }
        return $results;
    }
}
