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

class Torrent911 implements Provider
{
    use CrawlerInformationExtractor;

    // The listing only exposes cards (no size/seeds/magnet): each one costs a detail page request
    private const MAX_RESULTS = 20;

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
        $count = 0;
        foreach ($crawler->filter('div.banner-byx') as $item) {
            if ($count >= self::MAX_RESULTS) {
                break;
            }
            $itemCrawler = new Crawler($item);
            try {
                $link = $itemCrawler->filter('div.banner-title a')->first();
                $title = trim($link->attr('title') ?? $link->text());
                $detailCrawler = $this->initDomCrawler(
                    sprintf('%s%s', $this->providerInformation->getSearchUrl()->getBaseUrl(), $link->attr('href'))
                );
                $magnet = $detailCrawler->filter('a[href^="magnet:"]')->first()->attr('href');
                $size = Size::fromHumanSize($this->detailValue($detailCrawler, 'Poids du fichier'));
                $seeds = (int) trim($detailCrawler->filter('#retourSeeds')->text());
            } catch (\Exception $exception) {
                continue;
            }
            ++$count;
            $results->add(
                new ProviderResult(
                    ProviderType::provider($this->getName()),
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

    private function detailValue(Crawler $detailCrawler, string $label): string
    {
        foreach ($detailCrawler->filter('table tr') as $row) {
            $cells = (new Crawler($row))->filter('td');
            if ($cells->count() >= 2 && str_starts_with(trim($cells->eq(0)->text()), $label)) {
                return trim($cells->eq(1)->text());
            }
        }

        throw new \UnexpectedValueException($label);
    }
}
