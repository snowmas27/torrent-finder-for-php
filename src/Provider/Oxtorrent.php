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

class Oxtorrent implements Provider
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
        foreach ($crawler->filter('div.listing-torrent > table.table > tbody > tr') as $item) {
            $itemCrawler = new Crawler($item);
            try {
                $title = $itemCrawler->filter('td')->eq(0)->text();
                $size = Size::fromHumanSize($itemCrawler->filter('td')->eq(1)->text());
                $seeds = $itemCrawler->filter('td')->eq(2)->text();
                $crawlerDetailPage = $this->initDomCrawler(
                    sprintf(
                        '%s%s',
                        $this->providerInformation->getSearchUrl()->getBaseUrl(),
                        $itemCrawler->filter('td')->eq(0)->filter('a')->attr('href')
                    )
                );
                $magnet = $crawlerDetailPage->filter('div.btn-magnet > a')->attr('href');
            } catch (\Exception $exception) {
                continue;
            }
            $results->add(
                new ProviderResult(
                    $this->getName(),
                    TorrentData::fromMagnetURI($title, $magnet, $seeds, Resolution::guessFromString($title)),
                    $size
                )
            );
        }

        return $results->getResults();
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
