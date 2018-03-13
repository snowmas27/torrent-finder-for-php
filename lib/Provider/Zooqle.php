<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\ProviderSearchResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;

class Zooqle implements Provider
{
	use ExtractContentFromUrlProvider;
	private $searchUrl;
	private $name;

	public function __construct()
	{
		$this->name = ProvidersAvailable::ZOOQLE;
		$this->searchUrl = 'https://zooqle.com/search?q=%s&fmt=rss';
	}
	public function search(SearchQueryBuilder $keywords): ProviderSearchResult
	{
        $results = [];
		$url = sprintf($this->searchUrl, $keywords->urlize());
		/** @var Crawler $crawler */
		$crawler = $this->initDomCrawler($url);
		foreach ($crawler->filter('item') as $item) {
			$domCrawler = new Crawler($item);
			$title = $domCrawler->filter('title')->text();
			$length = $domCrawler->filterXPath('//torrent:contentLength')->text();
			$seeds = $domCrawler->filterXPath('//torrent:seeds')->text();
			$size = new Size((int) $length);
			$metaData = new TorrentData(
				$title,
				$domCrawler->filterXPath('//torrent:magnetURI')->text(),
				$seeds,
				$keywords->getFormat()
			);
            $results[] = new ProviderResult($this->name, $metaData, $size);
		}

		return new ProviderSearchResult($this->name, $results);
	}
}