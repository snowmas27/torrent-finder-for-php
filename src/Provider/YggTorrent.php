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
        $url = sprintf($this->providerInformation->getSearchUrl()->getUrl(), $keywords->urlize());
        /** @var Crawler $crawler */
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filter('table.cust-table tr') as $item) {
            try {
                $domCrawler = new Crawler($item);
                $td = $domCrawler->filter('td');
                $crawlerDetailPage = $this->initDomCrawler(
                    sprintf(
                        '%s%s',
                        $this->providerInformation->getSearchUrl()->getBaseUrl(),
                        $this->findAttribute($td->eq(0)->filter('a'), 'href')
                    )
                );
                $title = $this->findText($td->eq(0));
                $size = Size::fromHumanSize($this->findText($td->eq(1)));
                $seeds = $this->findText($td->eq(2));
                $metaData = TorrentData::fromMagnetURI(
                    $title,
                    $this->extractMagnet($crawlerDetailPage),
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

    private function extractMagnet(Crawler $detailPage): ?string
    {
        foreach ($detailPage->filter('div.download-btn') as $item) {
            $crawler = new Crawler($item);

            if (false === strpos($href = $crawler->filter('a')->attr('href'), 'magnet:')) {
                continue;
            }

            return $href;
        }

        throw new \InvalidArgumentException('No magnet was found');
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
