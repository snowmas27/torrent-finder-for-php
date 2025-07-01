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

class GkTorrents implements Provider
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
        foreach ($crawler->filter('div.block-detail')->nextAll()->filter('tbody > tr') as $item) {
            $crawlerResultList = new Crawler($item);
            $humanSize = $crawlerResultList->filter('td.liste-accueil-taille')->text();
            $seeds = $crawlerResultList->filter('td.liste-accueil-sources')->text();
            $crawlerDetailPage = $this->initDomCrawler(
                sprintf(
                    '%s%s',
                    $this->providerInformation->getSearchUrl()->getBaseUrl(),
                    $crawlerResultList->filter('td.liste-accueil-nom')->filter('a')->attr('href')
                )
            );

            $magnet = $crawlerDetailPage->filter('div.btn-magnet a')->attr('href');
            $title = $crawlerDetailPage->filter('div#torrentsdesc div.release')->text();

            if (null === $magnet) {
                continue;
            }

            $metaData = TorrentData::fromMagnetURI(
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
