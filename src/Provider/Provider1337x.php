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

class Provider1337x implements Provider
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
        foreach ($crawler->filter('div.table-list-wrap')->filter('tbody > tr') as $item) {
            $crawlerResultList = new Crawler($item);
            $td = $crawlerResultList->filter('td');

            $crawlerDetailPage = $this->initDomCrawler(
                sprintf(
                    '%s%s',
                    $this->providerInformation->getSearchUrl()->getBaseUrl(),
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

            $results->add(new ProviderResult(
                $this->providerInformation->getName(),
                $metaData,
                Size::fromHumanSize($td->eq(4)->text())
            ));
        }

        return $results->getResults();
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }

    private function extractMagnet(Crawler $detailPage): ?string
    {
        foreach ($detailPage->filter('div.torrent-detail-page')->filter('a') as $item) {
            $crawler = new Crawler($item);
            if (false === strpos($href = $crawler->attr('href'), 'magnet:')) {
                continue;
            }

            return $href;
        }

        return null;
    }
}
