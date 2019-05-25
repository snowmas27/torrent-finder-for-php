<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;
use TorrentFinder\VideoSettings\Resolution;
use TorrentFinder\VideoSettings\SizeFactory;

class Torrent4You implements Provider
{
   use ExtractContentFromUrlProvider;

   private $searchUrl;
   private $baseUrl;
   private $name;

   public function __construct()
   {
      $this->name = ProvidersAvailable::TORRENT4YOU;
      $this->baseUrl = 'http://torrent4you.me';
      $this->searchUrl = $this->baseUrl . '/search.php?s=%s&sort=seed';
   }

   public function search(SearchQueryBuilder $keywords): array
   {
      $results = [];
      $url = sprintf($this->searchUrl, strtolower($keywords->urlEncode()));
      /** @var Crawler $crawler */
      $crawler = $this->initDomCrawler($url);
       foreach ($crawler->filter('div#profile1 table.tb4 tr') as $item) {
           $itemCrawler = new Crawler($item);
           $title = trim($itemCrawler->filter('td')->eq(0)->text());
           $humanSize = trim($itemCrawler->filter('td')->eq(3)->text());
           $seeds = trim($itemCrawler->filter('td')->eq(5)->text());
           $torrentPage = sprintf(
               '%s/%s',
               $this->baseUrl,
               $itemCrawler->filter('td')->eq(0)->filter('a')->attr('href')
           );

           if (null === $hash = $this->getHash($torrentPage)) {
               continue;
           }

           $results[] = new ProviderResult(
               $this->name,
               new TorrentData(
                   $title,
                   sprintf('magnet:?xt=urn:btih:%s&dn=%s', $hash, $title),
                   (int) $seeds,
                   Resolution::guessFromString($title)
               ),
               SizeFactory::fromHumanSize($humanSize)
           );
       }

      return $results;
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
}
