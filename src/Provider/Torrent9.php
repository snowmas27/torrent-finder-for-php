<?php

namespace App\Provider;

use Symfony\Component\DomCrawler\Crawler;
use App\Provider\ResultSet\ProviderResult;
use App\Provider\ResultSet\TorrentData;
use App\Search\ExtractContentFromUrlProvider;
use App\Search\SearchQueryBuilder;
use App\VideoSettings\Size;
use App\VideoSettings\SizeFactory;
use App\VideoSettings\Resolution;

class Torrent9 implements Provider
{
    use ExtractContentFromUrlProvider;

    private $providerInformation;

    public function __construct(ProviderInformation $providerInformation)
    {
        $this->providerInformation = $providerInformation;
    }

    public function search(SearchQueryBuilder $keywords): array
    {
        $results = [];
        $url = sprintf($this->providerInformation->getSearchUrl()->getUrl(), $keywords->urlize());
        /** @var Crawler $crawler */
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filter('table.cust-table')->filter('tr') as $item) {
            $itemCrawler = new Crawler($item);
            $detailPageCrawler = $this->initDomCrawler(
                $this->providerInformation->getSearchUrl()->getBaseUrl() . $itemCrawler->filter('a')->attr('href')
            );

            $magnet = null;
            foreach ($detailPageCrawler->filter('div.download-btn') as $itemDetailPage) {
                $itemCrawlerDetailPage = new Crawler($itemDetailPage);

                if (false === preg_match('/^magnet:\?/i', $itemCrawlerDetailPage->filter('a')->attr('href'))) {
                    continue;
                }
                $magnet = $itemCrawlerDetailPage->filter('a')->attr('href');
            }

            if (null === $magnet) {
                continue;
            }

            $sizeValue = (float) $itemCrawler->filter('td')->eq(1)->text();
            $results[] = new ProviderResult(
                $this->providerInformation->getName(),
                $metaData = new TorrentData(
                    $title = trim($itemCrawler->filter('a')->text()),
                    $magnet,
                    $itemCrawler->filter('.seed_ok')->count() === 1
                        ? (int) $itemCrawler->filter('.seed_ok')->text()
                        : 0
                    ,
                    Resolution::guessFromString($title)
                ),
                SizeFactory::fromHumanSize(
                    sprintf(
                        '%s %s',
                        $sizeValue,
                        $sizeValue > 1000 ? Size::UNIT_GB : Size::UNIT_MB
                    )
                )
            );
        }

        return $results;
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
