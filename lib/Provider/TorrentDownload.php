<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\ProviderSearchResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;

class TorrentDownload implements Provider
{
	use ExtractContentFromUrlProvider;
	private $searchUrl;
	private $name;

	public function __construct()
	{
		$this->name = ProvidersAvailable::TORRENTDOWNLOAD;
		$this->searchUrl = 'http://www.torrentdownload.me/feed?q=%s';
	}

	public function search(SearchQueryBuilder $keywords): array
	{
		$results = [];
		$url = sprintf($this->searchUrl, $keywords->urlize());
		$crawler = $this->initDomCrawler($url);
		foreach ($crawler->filter('channel > item') as $item) {
			$crawlerResultList = new Crawler($item);
			$title = $crawlerResultList->filter('title')->text();
			// Seeds
			preg_match(
				'/Seeds: (\d+)/i',
				$crawlerResultList->filter('description')->text(),
				$match
			);
			$currentSeeds = $match[1] ?? 0;
			preg_match(
				'/Size: ((\d|\.)+ \w{2})/i',
				$crawlerResultList->filter('description')->text(),
				$match
			);
			$humanSize = $match[1] ?? '';
			if (strpos($humanSize, ' ') === false) {
				continue;
			}
			list($sizeValue, $unitValue) = explode(' ', $humanSize);

			$crawlerDetail = $this->initDomCrawler(
				$crawlerResultList->filter('guid')->text()
			);

			$torrent = '';
			foreach ($crawlerDetail->filterXPath('//table[contains(@class, \'table2\')]') as $table) {
				$crawlerDetailTable = new Crawler($table);
				if ($crawlerDetailTable->filterXPath('//tr//a[contains(@href, \'.torrent\')]')->count() === 0) {
					continue;
				}
				$torrent = $crawlerDetailTable
					->filterXPath('//tr//a[contains(@href, \'.torrent\')]/@href')->html();
			}
			try {
				$size = Size::fromHumanSize(sprintf('%f %s', $sizeValue, $unitValue));
				$metaData = new TorrentData(
					trim($title),
					$torrent,
					$currentSeeds,
					$keywords->getFormat()
				);
			} catch (\UnexpectedValueException $e) {
				continue;
			}
			$results[] = new ProviderResult($this->name, $metaData, $size);
		}
		return $results;
	}

}