<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;

class EzTv implements Provider
{
	use ExtractContentFromUrlProvider;
	private $searchUrl;
	private $name;

	public function __construct()
	{
		$this->name = ProvidersAvailable::EZTV;
		$this->searchUrl = 'https://eztv.ag/search/%s';
	}

	public function search(SearchQueryBuilder $keywords): array
	{
		$results = [];
		$url = sprintf($this->searchUrl, $keywords->urlize());
		/** @var Crawler $crawler */
		$crawler = $this->initDomCrawler($url);
		foreach ($crawler->filter('tr.forum_header_border') as $item) {
            $itemCrawler = new Crawler($item);
            $node = $itemCrawler->filter('a.magnet');
            if ($node->count() < 0) {
                continue;
            }

            if (empty($size = trim($itemCrawler->filter('td')->eq(3)->text()))) {
                continue;
            }

            if (!is_numeric($seeds = str_replace(',', '', $itemCrawler->filter('td')->eq(5)->text()))) {
                continue;
            }

			$metaData = new TorrentData(
                trim($itemCrawler->filter('td')->eq(1)->text()),
                trim($node->eq(0)->attr('href')),
                (int) $seeds,
				$keywords->getFormat()
			);
            $results[] = new ProviderResult(
                $this->name,
                $metaData,
                Size::fromHumanSize($size)
            );
		}

		return $results;
	}
}