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

        $resultsFound = (int) $this->findText($crawler->filter('div.browse-content h2 b'));
        if (1 !== $resultsFound) {

            return [];
        }

        $pageLink = $this->findAttribute($crawler->filter('div.browse-content section div.row a.browse-movie-link'), 'href');

        if (null === $pageLink) {

            return [];
        }
        $detailPageCrawler = $this->initDomCrawler($pageLink);

        foreach ($detailPageCrawler->filter('div.modal-content div.modal-torrent') as $item) {
            $itemCrawler = new Crawler($item);
            $resolutionValue = $this->findText($itemCrawler->filter('div.modal-quality'));
            if ($resolutionValue !== $keywords->getResolution()->getValue()) {

                continue;
            }

            $resolution = Resolution::guessFromString($resolutionValue);

            $type = $this->findText($itemCrawler->filter('p.quality-size')->eq(0));
            $humanSize = $this->findText($itemCrawler->filter('p.quality-size')->eq(1));

            if (null === $humanSize) {
                continue;
            }

            $size = Size::fromHumanSize($humanSize);

            $magnet = $this->findAttribute($itemCrawler->filter('a.magnet-download'), 'href');

            $seeds = $this->findSeeds($detailPageCrawler->filter('div#movie-tech-specs'), $resolution, $type);

            $metaData = new TorrentData(
                sprintf('%s %s', $keywords->getQuery(), $resolution->getValue()),
                $magnet,
                $seeds,
                $resolution
            );

            $results->add(new ProviderResult($this->providerInformation->getName(), $metaData, $size));
        }

        return $results->getResults();
    }

    private function findSeeds(Crawler $crawler, Resolution $resolution, string $type): int
    {
        $specPosition = 0;
        $index = 0;
        $typeMapping = [
            'BluRay' => 'BLU',
            'WEB' => 'WEB',
        ];
        foreach ($crawler->filter('span.tech-quality') as $span) {
            $crawlerSpan = new Crawler($span);
            if (preg_match(sprintf('/.*%s\.%s.*/i', $resolution->getValue(), $typeMapping[$type]), $crawlerSpan->text())) {
                $specPosition = $index;
                break;
            }
            $index++;
        }


        $peersAndSeedsString = $this->findText($crawler->filter('div.tech-spec-info span.tech-peers-seeds')->eq($specPosition)->parents());
        if (!preg_match('/\d+\s\/\s(\d+,?\d+)/i', $peersAndSeedsString, $match)) {

            return 0;
        }

        return (int) str_replace(',', '', $match[1]);
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
