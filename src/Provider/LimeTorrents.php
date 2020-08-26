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

class LimeTorrents implements Provider
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
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filter('channel > item') as $item) {
            $crawlerResultList = new Crawler($item);
            $title = $this->findText($crawlerResultList->filter('title'));
            preg_match(
                '/Seeds: (\d+)/i',
                $this->findText($crawlerResultList->filter('description')),
                $match
            );
            $currentSeeds = $match[1] ?? 0;
            $size = new Size((int) $this->findText($crawlerResultList->filter('size')));
            $link = $this->findText($crawlerResultList->filter('comments'));

            if (!$link) {
                continue;
            }

            $linkCrawler = $this->initDomCrawler($link);

            $href = null;
            foreach ($linkCrawler->filter('div.dltorrent') as $item) {
                $itemCrawler = new Crawler($item);
                $href = $this->findAttribute($itemCrawler->filter('a'), 'href');

                if (false !== strpos($href, 'magnet:', 0)) {
                    break;
                }
            }

            if (!$href) {
                continue;
            }
            $metaData = new TorrentData($title, $href, $currentSeeds, Resolution::guessFromString($title));
            $results->add(new ProviderResult($this->providerInformation->getName(), $metaData, $size));
        }
        return $results->getResults();
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
