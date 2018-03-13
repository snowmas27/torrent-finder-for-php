<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\ProviderSearchResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;

class Idope implements Provider
{
	use ExtractContentFromUrlProvider;
	private $searchUrl;
	private $name;

	public function __construct()
	{
		$this->name = ProvidersAvailable::IDOPE;
		$this->searchUrl = 'https://idope.se/torrent-list/%s';
	}

	public function search(SearchQueryBuilder $keywords): ProviderSearchResult
	{
		$results = [];
		$url = sprintf($this->searchUrl, $keywords->urlize());
		$crawler = $this->initDomCrawler($url);
		/** @var \DOMElement $domElement */
		foreach ($crawler->filterXPath("//div[@class='resultdiv']") as $domElement) {
			$crawlerListResult = new Crawler($domElement);
			$title = trim($crawlerListResult->filterXPath('//div[@class=\'resultdivtopname\']')->text());
			$humanSize = trim($crawlerListResult->filterXPath('//div[@class=\'resultdivbottonlength\']')->text());
			$currentSeeds = trim($crawlerListResult->filterXPath('//div[@class=\'resultdivbottonseed\']')->text());
			$magnet = sprintf(
				'magnet:?xt=urn:btih:%s',
				trim($crawlerListResult->filterXPath('//div[@class=\'hideinfohash\']')->text())
			);
			try {
				$size = Size::fromHumanSize($humanSize);
			} catch (\UnexpectedValueException $e) {
				continue;
			}
			$metaData = new TorrentData(
				trim($title),
				$magnet,
				$currentSeeds,
				$keywords->getFormat()
			);
            $results[] = new ProviderResult($this->name, $metaData, $size);
		}
		return new ProviderSearchResult($this->name, $results);
	}

}