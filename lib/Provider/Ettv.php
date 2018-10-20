<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\ExtractContentFromUrlProvider;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;

class Ettv implements Provider
{
	use ExtractContentFromUrlProvider;
	private $searchUrl;
	private $name;
    private $baseUrl;

	public function __construct()
	{
		$this->name = ProvidersAvailable::ETTV;
		$this->searchUrl = 'https://www.ettv.tv/torrents-search.php?search=%s&cat=0&incldead=0&freeleech=0&inclexternal=0&lang=0';
		$this->baseUrl = 'https://www.ettv.tv';
	}

	public function search(SearchQueryBuilder $keywords): array
	{
		$results = [];
		$url = sprintf($this->searchUrl, $keywords->urlize());
		/** @var Crawler $crawler */
		$crawler = $this->initDomCrawler($url);
		foreach ($crawler->filterXPath('//*[@id="main"]/div/div[2]/div/div[2]/div/table/tr') as $item) {
            $itemCrawler = new Crawler($item);
            $currentTd = $itemCrawler->filter('td');
            $titleTd = $currentTd->eq(1);
		    $title = trim($titleTd->text());
            $detailPageUrl = $this->baseUrl . $titleTd->filter('a')->attr('href');
            $size = Size::fromHumanSize($currentTd->eq(3)->text());
            $seeds = $currentTd->eq(3)->text();
            $detailPageCrawler = $this->initDomCrawler($detailPageUrl);
            if (!$magnet = $detailPageCrawler->filterXPath('//a[@class=\'download_link magnet\']')->attr('href')) {
                continue;
            }

			$metaData = new TorrentData(
                $title,
                trim($magnet),
                (int) $seeds,
				$keywords->getFormat()
			);
            $results[] = new ProviderResult(
                $this->name,
                $metaData,
                $size
            );
		}

		return $results;
	}
}