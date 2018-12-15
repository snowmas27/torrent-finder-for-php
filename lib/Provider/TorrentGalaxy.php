<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;

class TorrentGalaxy implements Provider
{
	use ExtractContentFromUrlProvider;
	private $searchUrl;
	private $name;

	public function __construct()
	{
		$this->name = ProvidersAvailable::TORRENTGALAXY;
		$this->searchUrl = 'https://torrentgalaxy.org/torrents.php?search=%s&sort=seeders&order=desc';
	}

	public function search(SearchQueryBuilder $keywords): array
	{
		$results = [];
		$url = sprintf($this->searchUrl, $keywords->urlize());
		$crawler = $this->initDomCrawler($url);
		foreach ($crawler->filter('div.tgxtable')->filter('div.tgxtablerow') as $item) {
			$crawlerResultList = new Crawler($item);
            $cell = $crawlerResultList->filter('div.tgxtablecell');
			$metaData = new TorrentData(
                $cell->eq(2)->filter('a')->text(),
                $cell->eq(3)->filter('a')->eq(1)->attr('href'),
                (int) $cell->eq(9)->filter('span > font')->eq(0)->text(),
				$keywords->getFormat()
			);
            $results[] = new ProviderResult($this->name, $metaData, Size::fromHumanSize($cell->eq(6)->text()));
		}

		return $results;
	}

}