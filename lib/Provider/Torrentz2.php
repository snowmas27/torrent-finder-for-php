<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;
use TorrentFinder\VideoSettings\Resolution;

class Torrentz2 implements Provider
{
    use ExtractContentFromUrlProvider;
    private $searchUrl;
    private $name;

    public function __construct()
    {
        $this->name = ProvidersAvailable::TORRENTZ2;
        $this->searchUrl = 'https://torrentz2.eu/feed?f=%s';
    }

    public function search(SearchQueryBuilder $keywords): array
    {
        $results = [];
        $url = sprintf($this->searchUrl, $keywords->urlize());
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filter('item') as $item) {
            $crawlerListResult = new Crawler($item);

            $title = $crawlerListResult->filter('title')->text();
            $description = $crawlerListResult->filter('description')->text();
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
                $size = Size::fromHumanSize($humanSize);
            } catch (\UnexpectedValueException $e) {
                continue;
            }
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
