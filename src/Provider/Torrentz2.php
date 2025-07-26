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

class Torrentz2 implements Provider
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
            $metaData = TorrentData::fromMagnetURI(
                trim($title),
                sprintf(
                    'magnet:?xt=urn:btih:%s&dn=%s',
                    $hash,
                    $title
                ),
                $seeds,
                Resolution::guessFromString($title)
            );
            $results->add(new ProviderResult(
                ProviderType::provider($this->providerInformation->getName()),
                $metaData,
                $size
            ));
        }

        return $results->getResults();
    }

    private function extractDescription(string $pattern, string $description): ?string
    {
        preg_match($pattern, $description, $match);

        if (empty($match[1])) {

            return null;
        }

        return $match[1];
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
