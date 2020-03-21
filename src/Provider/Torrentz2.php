<?php

namespace App\Provider;

use Symfony\Component\DomCrawler\Crawler;
use App\Provider\ResultSet\ProviderResult;
use App\Provider\ResultSet\TorrentData;
use App\Search\ExtractContentFromUrlProvider;
use App\Search\SearchQueryBuilder;
use App\VideoSettings\Size;
use App\VideoSettings\Resolution;

class Torrentz2 implements Provider
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
            $results[] = new ProviderResult($this->providerInformation->getName(), $metaData, $size);
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

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
