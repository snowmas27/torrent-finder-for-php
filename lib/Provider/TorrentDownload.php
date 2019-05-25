<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Resolution;
use TorrentFinder\VideoSettings\SizeFactory;

class TorrentDownload implements Provider
{
    use ExtractContentFromUrlProvider;
    private $searchUrl;
    private $name;

    public function __construct()
    {
        $this->name = ProvidersAvailable::TORRENTDOWNLOAD;
        $this->searchUrl = 'http://www.torrentdownload.me/feed?q=%s';
    }

    public function search(SearchQueryBuilder $keywords): array
    {
        $results = [];
        $url = sprintf($this->searchUrl, $keywords->urlize());
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filter('channel > item') as $item) {
            $crawlerResultList = new Crawler($item);
            $title = $crawlerResultList->filter('title')->text();
            $description = $crawlerResultList->filter('description')->text();
            if (null === $humanSize = $this->extractDescription('/Size: ([\d+\.]+ \w{2})/i', $description)) {
                continue;
            }

            if (null === $seeds = $this->extractDescription('/Seeds: (\d+)/i', $description)) {
                continue;
            }

            if (null === $hash = $this->extractDescription('/Hash: (\w+)/i', $description)) {
                continue;
            }

            try {
                $size = SizeFactory::fromHumanSize($humanSize);
                $metaData = new TorrentData(
                    trim($title),
                    sprintf(
                        'magnet:?xt=urn:btih:%s&dn=%s',
                        $hash,
                        $title
                    ),
                    $seeds,
                    Resolution::guessFromString($title)
                );
            } catch (\UnexpectedValueException $e) {
                continue;
            }
            $results[] = new ProviderResult($this->name, $metaData, $size);
        }
        return $results;
    }

    private function extractDescription(string $pattern, string $description): ?string
    {
        preg_match($pattern, $description, $match);

        if (empty($match[1])) {

            return null;
        }

        return $match[1];
    }
}
