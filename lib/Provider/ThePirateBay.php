<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;
use TorrentFinder\VideoSettings\SizeFactory;

class ThePirateBay implements Provider
{
	use ExtractContentFromUrlProvider;
	private $searchUrl;
	private $name;
	private $baseUrl;

	public function __construct()
	{
		$this->name = ProvidersAvailable::THE_PIRATE_BAY;
		$this->searchUrl = 'https://thepiratebay3.org/index.php?q=%s&category=0&page=0&orderby=99';
	}

	public function search(SearchQueryBuilder $keywords): array
	{
        $results = [];
		$url = sprintf($this->searchUrl, $keywords->urlize());
		$crawler = $this->initDomCrawler($url);
        foreach ($crawler->filter('table#searchResult tr') as $item) {
            $itemCrawler = new Crawler($item);
            if (1 >= $itemCrawler->filter('td')->count()) {
                continue;
            }
            $title = trim($itemCrawler->filter('td')->eq(1)->filter('div.detName')->text());
            $magnet = $itemCrawler
                ->filter('td')
                ->eq(1)
                ->filter('a')
                ->eq(1)
                ->attr('href')
            ;
            preg_match(
                '/Size ([\.\w\s]+) (\w{2,3})/i',
                $itemCrawler->filter('font.detDesc')->text(),
                $match
            );

            if (empty($match[1])) {
                continue;
            }

            $results[] = new ProviderResult(
                $this->name,
                new TorrentData(
                    $title,
                    $magnet,
                    (int) $itemCrawler->filter('td')->eq(2)->text(),
                    $keywords->getFormat()
                ),
                SizeFactory::convertFromWeirdFormat($match[1], $match[2])
            );
		}

		return $results;
	}

}
