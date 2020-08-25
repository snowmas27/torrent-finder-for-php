<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\ProviderResults;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\CrawlerInformationExtractor;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Resolution;
use TorrentFinder\VideoSettings\SizeFactory;

class Torrent4You implements Provider
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
       $url = sprintf($this->providerInformation->getSearchUrl()->getUrl(), strtolower($keywords->urlEncode()));
      /** @var Crawler $crawler */
      $crawler = $this->initDomCrawler($url);
       foreach ($crawler->filter('div#profile1 table.tb4 tr') as $item) {
           $itemCrawler = new Crawler($item);
           $title = trim($itemCrawler->filter('td')->eq(0)->text());
           $humanSize = trim($itemCrawler->filter('td')->eq(3)->text());
           $seeds = trim($itemCrawler->filter('td')->eq(5)->text());
           $torrentPage = sprintf(
               '%s/%s',
               $this->providerInformation->getSearchUrl()->getBaseUrl(),
               $itemCrawler->filter('td')->eq(0)->filter('a')->attr('href')
           );

           if (null === $hash = $this->getHash($torrentPage)) {
               continue;
           }

           $results->add(new ProviderResult(
               $this->providerInformation->getName(),
               new TorrentData(
                   $title,
                   sprintf('magnet:?xt=urn:btih:%s&dn=%s', $hash, $title),
                   (int) $seeds,
                   Resolution::guessFromString($title)
               ),
               SizeFactory::fromHumanSize($humanSize)
           ));
       }

      return $results->getResults();
   }

   private function getHash(string $torrentPage): ?string
   {
       $torrentPageCrawler = $this->initDomCrawler($torrentPage);
       foreach ($torrentPageCrawler->filter('div#profile1')->eq(0)->filter('tr') as $item) {
           $itemCrawler = new Crawler($item);
           if ('Hash:' !== trim($itemCrawler->filter('td')->eq(0)->text())) {
               continue;
           }

           return trim($itemCrawler->filter('td')->eq(1)->text());
       }

       return null;
   }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
