<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\ProviderSearchResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;

class Demonoid implements Provider
{
	use ExtractContentFromUrlProvider;
	private $searchUrl;
	private $name;
	private $baseUrl;

	public function __construct()
	{
		$this->name = ProvidersAvailable::DEMONOID;
		$this->searchUrl = 'https://www.demonoid.pw/files/?subcategory=All&quality=All&seeds=0&external=2&query=%s&iud=0&sort=S';
		$this->baseUrl = 'https://www.demonoid.pw';
	}

	public function search(SearchQueryBuilder $keywords): ProviderSearchResult
	{
        $results = [];
		$url = sprintf($this->searchUrl, $keywords->urlize());
		$crawler = $this->initDomCrawler($url);
		$tdList = '';
		/** @var \DOMElement $domElement */
		foreach ($crawler->filterXPath('//table[17]/tr') as $domElement) {
			$crawlerListResult = new Crawler($domElement);
			if ($crawlerListResult->filter('td')->count() === 2
				|| $crawlerListResult->filter('td')->count() === 8) {
				$tdList .= $crawlerListResult->html();
			}
			if ($crawlerListResult->filter('td')->count() === 8) {
				$crawlerTdList = new Crawler($tdList);
				$title = $crawlerTdList->filterXPath('//td[2]')->text();
				$torrent = $crawlerTdList->filterXPath('//td[5]/a/@href')->text();
				$humanSize = $crawlerTdList->filterXPath('//td[6]')->text();
				$currentSeeds = $crawlerTdList->filterXPath('//td[8]')->text();
				list($value, $unit) = explode(' ', $humanSize);
				try {
					$value = str_replace(',', '', $value);
					$size = new Size($value, $unit);
				} catch (\UnexpectedValueException $e) {
					continue;
				}
				$metaData = new TorrentData(
					trim($title),
					$torrent,
					$currentSeeds,
					$keywords->getFormat()
				);
                $results[] = new ProviderResult($this->name, $metaData, $size);
				$tdList = '';
			}
		}
		return new ProviderSearchResult($this->name, $results);
	}

}