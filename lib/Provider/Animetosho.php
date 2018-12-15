<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;

class Animetosho implements Provider
{
	use ExtractContentFromUrlProvider;
	private $searchUrl;
	private $name;
	private $baseUrl;

	public function __construct()
	{
		$this->name = ProvidersAvailable::ANIMEOSHO;
		$this->searchUrl = 'https://feed.animetosho.org/rss2?q=%s';
	}

	public function search(SearchQueryBuilder $keywords): array
	{
        $results = [];
		$url = sprintf($this->searchUrl, $keywords->urlize());
		$crawler = $this->initDomCrawler($url);

        foreach ($crawler->filter('item') as $item) {
            $itemCrawler = new Crawler($item);

            preg_match(
                '/<strong>Total Size<\/strong>: ([\.\w\s]+)/i',
                $itemCrawler->filter('description')->html(),
                $match
            );

            if (empty($match[1])) {
                continue;
            }

            $metaData = new TorrentData(
                $itemCrawler->filter('title')->text(),
                $itemCrawler->filter('enclosure')->attr('url'),
                10,
                $keywords->getFormat()
            );
            $results[] = new ProviderResult($this->name, $metaData, Size::fromHumanSize($match[1]));
		}

		return $results;
	}

}
