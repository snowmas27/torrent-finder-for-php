<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\ProviderResults;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\CrawlerInformationExtractor;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;
use TorrentFinder\VideoSettings\SizeFactory;
use TorrentFinder\VideoSettings\Resolution;

class Torrent9 implements Provider
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
        $url = sprintf($this->providerInformation->getSearchUrl()->getUrl(), $keywords->urlize('-'));
        /** @var Crawler $crawler */
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filter('div.table-responsive table')->filter('tr') as $item) {
            $itemCrawler = new Crawler($item);
            $detailPageCrawler = $this->initDomCrawler(
                $this->providerInformation->getSearchUrl()->getBaseUrl() . $itemCrawler->filter('a')->attr('href')
            );

            $magnet = null;
            foreach ($detailPageCrawler->filter('div.download-btn') as $itemDetailPage) {
                $itemCrawlerDetailPage = new Crawler($itemDetailPage);

                if (!preg_match('/^magnet:\?/i', $itemCrawlerDetailPage->filter('a')->attr('href'))) {
                    continue;
                }
                $magnet = $itemCrawlerDetailPage->filter('a')->attr('href');
            }

            if (null === $magnet) {
                continue;
            }

            $sizeValue = $itemCrawler->filter('td')->eq(1)->text();
            $results->add(new ProviderResult(
                $this->providerInformation->getName(),
                $metaData = new TorrentData(
                    $title = trim($detailPageCrawler->filter('p.description_torrent')->text()),
                    $magnet,
                    $itemCrawler->filter('.seed_ok')->count() === 1
                        ? (int) $itemCrawler->filter('.seed_ok')->text()
                        : 0
                    ,
                    Resolution::guessFromString($title)
                ),
                SizeFactory::fromHumanSize($sizeValue)
            ));
        }

        return $results->getResults();
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
