<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\CrawlerInformationExtractor;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;
use TorrentFinder\VideoSettings\Resolution;

class Magnet4You implements Provider
{
    use CrawlerInformationExtractor;

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

                $title = trim($this->findText($crawlerListResult->filterXPath('//td[1]/a[2]')));
                $magnet = $this->findText($crawlerListResult->filterXPath('//td[1]/a[1]/@href'));
                $humanSize = trim($this->findText($crawlerListResult->filterXPath('//td[3]')));
                try {
                    $size = Size::fromHumanSize($humanSize);
                } catch (\UnexpectedValueException $e) {
                    continue;
                }
                $currentSeeds = trim($this->findText($crawlerListResult->filterXPath('//td[5]')));
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
