<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;

class Btdb implements Provider
{
	use ExtractContentFromUrlProvider;
	private $searchUrl;
	private $name;

	public function __construct()
	{
		$this->name = ProvidersAvailable::BTDB;
		$this->searchUrl = 'https://btdb.to/q/%s';
	}

	public function search(SearchQueryBuilder $keywords): array
	{
		$results = [];
		$url = sprintf($this->searchUrl, $keywords->urlize());
		/** @var Crawler $crawler */
		$crawler = $this->initDomCrawler($url);
		foreach ($crawler->filter('ul.search-ret-list')->filter('li.search-ret-item') as $item) {
            $itemCrawler = new Crawler($item);
            $metaInfoNode = $itemCrawler->filter('div.item-meta-info');
            $humanSize = $metaInfoNode->filter('span.item-meta-info-value')->eq(0)->text();

            $results[] = new ProviderResult(
                $this->name,
                $metaData = new TorrentData(
                    $itemCrawler->filter('h2.item-title')->filter('a')->attr('title'),
                    $metaInfoNode->filter('a')->attr('href'),
                    $metaInfoNode->filter('span.item-meta-info-value')->eq(3)->text(),
                    $keywords->getFormat()
                ),
                Size::fromHumanSize($humanSize)
            );
		}

		return $results;
	}
}