<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;
use TorrentFinder\VideoSettings\SizeFactory;
use TorrentFinder\VideoSettings\Resolution;

class Btdb implements Provider
{
    use ExtractContentFromUrlProvider;
    private $searchUrl;
    private $name;

    public function __construct()
    {
        $this->name = ProvidersAvailable::BTDB;
        $this->searchUrl = 'https://btdb.eu/?search=%s';
    }

    public function search(SearchQueryBuilder $keywords): array
    {
        $results = [];
        $url = sprintf($this->searchUrl, $keywords->urlize());
        /** @var Crawler $crawler */
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filter('div.search-ret li.search-ret-item') as $item) {
            $domCrawler = new Crawler($item);
            if (null === $title = $this->findVideo($domCrawler)) {
                continue;
            }

            $itemMetaInfo = $domCrawler->filter('div.item-meta-info');
            $magnet = $itemMetaInfo->filter('a.magnet')->attr('href');
            $itemMetaInfoValue = $itemMetaInfo->filter('span.item-meta-info-value');
            $size = Size::fromHumanSize($itemMetaInfoValue->eq(0)->text());
            $seeds = $itemMetaInfoValue->eq(3)->text();
            $metaData = new TorrentData($title, $magnet, $seeds, Resolution::guessFromString($title));
            $results[] = new ProviderResult($this->name, $metaData, $size);
        }

        return $results;
    }

    public function findVideo(Crawler $domCrawler): ?string
    {
        $extensions = ['avi', 'mp4', 'mkv'];
        foreach ($domCrawler->filter('li.file span.file-name') as $item) {
            $fileCrawler = new Crawler($item);
            if (preg_match(sprintf('/\.(%s)/i', implode('|', $extensions)), $fileCrawler->text())) {

                return trim($fileCrawler->text());
            }
        }

        return null;
    }
}
