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

class YggTorrent implements Provider
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
        $url = sprintf($this->providerInformation->getSearchUrl()->getUrl(), $keywords->rawUrlEncode());
        /** @var Crawler $crawler */
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filter('table.cust-table tr') as $item) {
            try {
                $domCrawler = new Crawler($item);
                $td = $domCrawler->filter('td');
                if ($td->count() < 7) {
                    continue;
                }
                $title = $this->findText($td->eq(1)->filter('a'));
                $detailUrl = sprintf(
                    '%s%s',
                    $this->providerInformation->getSearchUrl()->getBaseUrl(),
                    $this->findAttribute($td->eq(1)->filter('a'), 'href')
                );
                $size = Size::fromHumanSize($this->findText($td->eq(4)));
                $seeds = (int) $this->findText($td->eq(6)->filter('span.seed_ok'));
                $magnet = $this->findFirstMagnetUrl($detailUrl);
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
                    ProviderType::provider($this->providerInformation->getName()),
                    $metaData,
                    $size
                ));
            } catch (\Exception $exception) {
                continue;
            }
        }

        return $results->getResults();
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
