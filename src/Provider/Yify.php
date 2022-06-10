<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\ProviderResults;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\CrawlerInformationExtractor;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Resolution;
use TorrentFinder\VideoSettings\Size;

class Yify implements Provider
{
    use CrawlerInformationExtractor;

    private $providerInformation;

    public function __construct(ProviderInformation $providerInformation)
    {
        $this->providerInformation = $providerInformation;
    }

    public function search(SearchQueryBuilder $keywords): array
    {
        $results = new ProviderResults();
        $url = sprintf($this->providerInformation->getSearchUrl()->getUrl(), $keywords->urlEncode(false));
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filter('div.browse-movie-wrap') as $item) {
            $itemCrawler = new Crawler($item);
            $title = $itemCrawler->filter('a.browse-movie-title')->text();
            $year = $itemCrawler->filter('div.browse-movie-year')->text();
            $fullTitle = "$title $year";
            $pageLink = $itemCrawler->filter('a.browse-movie-title')->attr('href');
            $detailPageCrawler = $this->initDomCrawler($pageLink);
            foreach ($detailPageCrawler->filter('div.modal-torrent') as $modalTorrent) {
                $modalTorrentCrawler = new Crawler($modalTorrent);
                $resolution = Resolution::guessFromString($modalTorrentCrawler->filter('div.modal-quality')->text());
                $fullTitleResolution = sprintf('%s %s', $fullTitle, $resolution->getValue());
                try {
                    $size = Size::fromHumanSize($modalTorrentCrawler->filter('p.quality-size')->eq(1)->text());
                    $magnet = $modalTorrentCrawler->filter('a.magnet-download')->attr('href');
                } catch (\Exception $exception) {
                    continue;
                }
                $results->add(new ProviderResult(
                    $this->getName(),
                    new TorrentData($fullTitleResolution, $magnet, 100, $resolution),
                    $size
                ));
            }
        }

        return $results->getResults();
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
