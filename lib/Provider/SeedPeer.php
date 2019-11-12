<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;
use TorrentFinder\VideoSettings\Resolution;

class SeedPeer implements Provider
{
   use ExtractContentFromUrlProvider;
   private $searchUrl;
   private $name;

   public function __construct()
   {
      $this->name = ProvidersAvailable::SEEDPEER;
      $this->searchUrl = 'https://www.seedpeer.me/search/%s';
   }

   public function search(SearchQueryBuilder $keywords): array
   {
      $results = [];
      $url = sprintf($this->searchUrl, strtolower($keywords->urlize('-')));
      /** @var Crawler $crawler */
      $crawler = $this->initDomCrawler($url);

      if (false === preg_match('/window\.initialData=(.*)/i', $crawler->text(), $match)) {

          return [];
      }

      if (empty($match)) {

          return [];
      }

      $rawResults = json_decode(trim($match[1]), true);

      if (empty($rawResults['data']['list'])) {

          return [];
      }

      foreach ($rawResults['data']['list'] as $datum) {
          $results[] = new ProviderResult(
            $this->name,
            $metaData = new TorrentData(
                $title = $datum['name'],
                sprintf('magnet:?xt=urn:btih:%s&dn=%s', $datum['hash'], urlencode($datum['name'])),
                $datum['seeds'],
                Resolution::guessFromString($title)
            ),
            new Size($datum['size'])
        );
      }

      return $results;
   }
}
