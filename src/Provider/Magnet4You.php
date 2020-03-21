<?php

namespace App\Provider;

use Symfony\Component\DomCrawler\Crawler;
use App\Provider\ResultSet\ProviderResult;
use App\Provider\ResultSet\TorrentData;
use App\Search\ExtractContentFromUrlProvider;
use App\Search\SearchQueryBuilder;
use App\VideoSettings\Size;
use App\VideoSettings\Resolution;

class Magnet4You implements Provider
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
        try {
            $crawler = $this->initDomCrawler($url);
            foreach ($crawler->filterXPath("//div[@id='profile1']/table[@class='tb4']/tr") as $domElement) {
                $crawlerListResult = new Crawler($domElement);

                $title = trim($crawlerListResult->filterXPath('//td[1]/a[2]')->text());
                $magnet = $crawlerListResult->filterXPath('//td[1]/a[1]/@href')->text();
                $humanSize = trim($crawlerListResult->filterXPath('//td[3]')->text());
                try {
                    $size = Size::fromHumanSize($humanSize);
                } catch (\UnexpectedValueException $e) {
                    continue;
                }
                $currentSeeds = trim($crawlerListResult->filterXPath('//td[5]')->text());
                $metaData = new TorrentData(
                    trim($title),
                    $magnet,
                    $currentSeeds,
                    Resolution::guessFromString($title)
                );
                $results[] = new ProviderResult($this->providerInformation->getName(), $metaData, $size);
            }
        } catch (\UnexpectedValueException $e) {
        } catch (\InvalidArgumentException $e) {
        }
        return $results;
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
