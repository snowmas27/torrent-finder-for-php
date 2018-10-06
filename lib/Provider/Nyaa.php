<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\SizeFactory;

class Nyaa implements Provider
{
	use ExtractContentFromUrlProvider;
	private $searchUrl;
	private $name;
	private $baseUrl;

	public function __construct()
	{
		$this->name = ProvidersAvailable::NYAA;
		$this->searchUrl = 'https://nyaa.si/?f=0&c=1_0&q=%s&s=seeders&o=desc';
		$this->baseUrl = 'https://nyaa.si';
	}

	public function search(SearchQueryBuilder $keywords): array
	{
        $results = [];
		$url = sprintf($this->searchUrl, $keywords->urlize());
		$crawler = $this->initDomCrawler($url);
		/** @var \DOMElement $domElement */
        foreach ($crawler->filter('table.torrent-list')->filter('tr.success') as $item) {
            $itemCrawler = new Crawler($item);
            $td = $itemCrawler->filter('td');
            $index = 2 === $td->eq(1)->filter('a')->count() ? 1 : 0;
            list($size, $unit) = explode(' ', $td->eq(3)->text());
            $size = SizeFactory::convertFromWeirdFormat($size, $unit);
            $metaData = new TorrentData(
                trim($td->eq(1)->filter('a')->eq($index)->text()),
                $td->eq(2)->filter('a')->eq(1)->attr('href'),
                $td->eq(5)->text(),
                $keywords->getFormat()
            );
            $results[] = new ProviderResult($this->name, $metaData, $size);
		}
		return $results;
	}

}
