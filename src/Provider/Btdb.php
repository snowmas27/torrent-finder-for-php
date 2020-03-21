<?php

namespace App\Provider;

use Symfony\Component\DomCrawler\Crawler;
use App\Provider\ResultSet\ProviderResult;
use App\Provider\ResultSet\TorrentData;
use App\Search\ExtractContentFromUrlProvider;
use App\Search\SearchQueryBuilder;
use App\VideoSettings\Size;
use App\VideoSettings\Resolution;

class Btdb implements Provider
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
        /** @var Crawler $crawler */
        $crawler = $this->initDomCrawler($url);
        foreach ($crawler->filter('div.card-body div.media') as $item) {
            $domCrawler = new Crawler($item);
            if (null === $title = $this->findVideo($domCrawler)) {
                continue;
            }

            $magnet = $domCrawler->filter('div.media-right > a')->attr('href');
            $itemMetaCrawler = $domCrawler->filter('div.item-meta-info small');
            $size = Size::fromHumanSize($itemMetaCrawler->eq(0)->filter('strong')->text());
            $seeds = $itemMetaCrawler->eq(2)->filter('strong')->text();
            $metaData = new TorrentData($title, $magnet, $seeds, Resolution::guessFromString($title));
            $results[] = new ProviderResult($this->providerInformation->getName(), $metaData, $size);
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

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
