<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\ProviderSearchResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;
use TorrentFinder\VideoSettings\SizeFactory;

class Extratorrent implements Provider
{
	use ExtractContentFromUrlProvider;
	private $searchUrl;
	private $name;

	public function __construct()
	{
		$this->name = ProvidersAvailable::EXTRATORRENT;
		$this->searchUrl = 'https://extratorrent.cd/search/?search=%s&new=1&x=0&y=0';
	}
	public function search(SearchQueryBuilder $keywords): ProviderSearchResult
	{
		$results = [];
		$url = sprintf($this->searchUrl, $keywords->urlize());
		/** @var Crawler $crawler */
		$crawler = $this->initDomCrawler($url);
		foreach ($crawler->filterXPath('//table[@class=\'tl\']/tr[@class=\'tlr\']') as $item) {
			$domCrawler = new Crawler($item);
            list($value, $unit) = explode(' ', $domCrawler->filterXPath('//td[5]')->text());
            $size = SizeFactory::convertFromWeirdFormat($value, $unit);
			$seeds = $domCrawler->filterXPath('//td[6]')->text();
			$metaData = new TorrentData(
                trim($domCrawler->filterXPath('//td[3]/a[1]')->text()),
                trim($domCrawler->filterXPath('//td[1]/a[2]/@href')->text()),
                $seeds,
				$keywords->getFormat()
			);
            $results[] = new ProviderResult($this->name, $metaData, $size);
		}

		return new ProviderSearchResult($this->name, $results);
	}
}