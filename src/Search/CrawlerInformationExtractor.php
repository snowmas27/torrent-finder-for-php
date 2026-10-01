<?php

namespace TorrentFinder\Search;

use Symfony\Component\DomCrawler\Crawler;

trait CrawlerInformationExtractor
{
    public function initDomCrawler(string $url): Crawler
    {
        return new Crawler($this->fileGetContentsCurl($url));
    }

    public function findAttribute(Crawler $crawler, string $attribute): ?string
    {
        if (0 === $crawler->count()) {

            return null;
        }

        return $crawler->attr($attribute);
    }

    public function findText(Crawler $crawler): ?string
    {
        if (0 === $crawler->count()) {

            return null;
        }

        return trim($crawler->text());
    }

    public function findFirstMagnetUrl(string $url): ?string
    {
        $crawler = new Crawler($this->fileGetContentsCurl($url));

        $magnetNodes = $crawler->filter('a[href*="magnet:"]');

        if (0 === $magnetNodes->count()) {
            return null;
        }
        return $magnetNodes->first()->attr('href');
    }

    private function fileGetContentsCurl(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        curl_close($ch);

        if (false === $data) {
            throw new \UnexpectedValueException("$url is unreachable");
        }

        if (false !== strpos($data, 'Please turn JavaScript on and reload the page.')) {
            throw new \UnexpectedValueException("$url is Cloudflare protected");
        }


        return $data;
    }
}
