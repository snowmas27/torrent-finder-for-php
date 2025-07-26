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

class Btdb implements Provider
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
        /** @var Crawler $crawler */
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filter('div.card-body div.media') as $item) {
            $domCrawler = new Crawler($item);
            if (null === $title = $this->findVideo($domCrawler)) {
                continue;
            }

            $magnet = $this->findAttribute($domCrawler->filter('div.media-right > a'), 'href');
            $itemMetaCrawler = $domCrawler->filter('div.item-meta-info small');
            $size = Size::fromHumanSize(
                $this->findText($itemMetaCrawler->eq(0)->filter('strong'))
            );
            $seeds = $this->findText($itemMetaCrawler->eq(2)->filter('strong'));
            $metaData = TorrentData::fromMagnetURI($title, $magnet, $seeds, Resolution::guessFromString($title));
            $results->add(new ProviderResult(
                ProviderType::provider($this->providerInformation->getName()),
                $metaData,
                $size
            ));
        }

        return $results->getResults();
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

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
