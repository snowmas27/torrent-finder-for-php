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

class Torlock implements Provider
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
        foreach ($crawler->filterXPath('//html/body/article/div[2]/table/tr') as $item) {
            $line = new Crawler($item);
            $cell = $line->filter('td');

            if (0 === $cell->eq(0)->count()) {
                continue;
            }

            $title = str_replace(['<mark>', '</mark>'], ' ', $cell->eq(0)->filter('a')->filter('b')->html());
            $title = str_replace('  ', ' ', $title);

            if (0 === $cell->eq(2)->count()) {
                continue;
            }
            $size = Size::fromHumanSize($cell->eq(2)->text());

            if (0 === $cell->eq(3)->count()) {
                continue;
            }

            $seeds = (int) $cell->eq(3)->text();

            $detailPage = $this->initDomCrawler(
                $this->providerInformation->getSearchUrl()->getBaseUrl() . $cell->eq(0)->filter('a')->attr('href')
            );


            if (null === $magnet = $this->extractMagnet($detailPage)) {
                continue;
            }

            $metaData = TorrentData::fromMagnetURI($title, $magnet, $seeds, Resolution::guessFromString($title));

            $results->add(new ProviderResult(
                ProviderType::provider($this->providerInformation->getName()),
                $metaData,
                $size
            ));
        }

        return $results->getResults();
    }

    private function extractMagnet(Crawler $detailPage): ?string
    {
        foreach ($detailPage->filterXPath('//html/body/article/table[2]') as $item) {
            $crawler = new Crawler($item);

            if (false === strpos($href = $crawler->filter('a')->attr('href'), 'magnet:')) {
                continue;
            }

            return $href;
        }

        return null;
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
