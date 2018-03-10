<?php

namespace TorrentFinder\Search;

use Symfony\Component\DomCrawler\Crawler;

trait ExtractContentFromUrlProvider
{
	public function initDomCrawler(string $url): Crawler
	{
		$content = $this->fileGetContentsCurl($url);
		if (false === $content) {
			throw new \UnexpectedValueException("$url is unreachable");
		}

		return new Crawler($content);
	}

	private function fileGetContentsCurl(string $url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
		curl_setopt($ch, CURLOPT_ENCODING, "gzip");
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$data = curl_exec($ch);
		curl_close($ch);

		return $data;
	}
}