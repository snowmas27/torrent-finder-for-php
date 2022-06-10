<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\ProviderResults;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\CrawlerInformationExtractor;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;
use TorrentFinder\VideoSettings\Resolution;

class KickassTorrents implements Provider
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
        $url = sprintf($this->providerInformation->getSearchUrl()->getUrl(), $keywords->urlize());
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filter('table.frontPageWidget tr') as $item) {
            $crawlerResultList = new Crawler($item);
            $titleDom = $crawlerResultList->filter('td div.torrentname a.cellMainLink');
            if ($titleDom->count() === 0) {
                continue;
            }
            $title = trim($titleDom->text());
            $humanSize = trim($crawlerResultList->filter('td.nobr')->text());
            $seeds = trim(trim($crawlerResultList->filter('td.green')->text()));
            $crawlerDetailPage = $this->initDomCrawler(
                sprintf(
                    '%s%s',
                    $this->providerInformation->getSearchUrl()->getBaseUrl(),
                    $titleDom->attr('href')
                )
            );

            $magnet = $crawlerDetailPage->filter('div.downloadButtonGroup a')->eq(0)->attr('href');
            $metaData = new TorrentData(
                $title,
                $magnet,
                $seeds,
                Resolution::guessFromString($title)
            );

            $results->add(new ProviderResult(
                $this->providerInformation->getName(),
                $metaData,
                Size::fromHumanSize($humanSize)
            ));
        }

        return $results->getResults();
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
