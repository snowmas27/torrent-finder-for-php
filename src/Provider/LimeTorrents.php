<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\ProviderResults;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\CrawlerInformationExtractor;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Size;
use TorrentFinder\VideoSettings\SizeFactory;
use TorrentFinder\VideoSettings\Resolution;

class LimeTorrents implements Provider
{
    use CrawlerInformationExtractor;

    private $providerInformation;

    public function __construct(ProviderInformation $providerInformation)
    {
        $this->providerInformation = $providerInformation;
    }

    public function search(SearchQueryBuilder $keywords): array
    {
        $results = new ProviderResults();
        $url = sprintf($this->providerInformation->getSearchUrl()->getUrl(), $keywords->rawUrlEncode());
        $crawler = $this->initDomCrawler($url);

        // Parse la table contenant les résultats
        foreach ($crawler->filter('table.table2 tr') as $row) {
            $rowCrawler = new Crawler($row);

            // Ignore les en-têtes de table
            if ($rowCrawler->filter('th')->count() > 0) {
                continue;
            }

            $cells = $rowCrawler->filter('td');
            if ($cells->count() < 6) {
                continue;
            }

            // Nom du torrent et lien vers la page de détail
            $titleCell = $cells->eq(0);
            $titleLink = $titleCell->filter('.tt-name a[href*="-torrent-"]');
            if ($titleLink->count() === 0) {
                continue;
            }

            $title = $this->findText($titleLink);
            $detailPageUrl = $this->findAttribute($titleLink, 'href');

            // Ajout du domaine de base si le lien est relatif
            if (strpos($detailPageUrl, 'http') !== 0) {
                $detailPageUrl = 'https://www.limetorrents.to' . $detailPageUrl;
            }

            // Taille (3ème colonne)
            $sizeText = $this->findText($cells->eq(2));
            $size = SizeFactory::fromHumanSize($sizeText);

            // Seeds (4ème colonne)
            $currentSeeds = (int) $this->findText($cells->eq(3));

            // Leechers (5ème colonne) 
            $currentLeechers = (int) $this->findText($cells->eq(4));

            // Récupération du lien de téléchargement direct du torrent
            $downloadLink = $titleCell->filter('a.csprite_dl14');
            if ($downloadLink->count() === 0) {
                continue;
            }

            $torrentUrl = $this->findAttribute($downloadLink, 'href');

            if (!$torrentUrl || !$title) {
                continue;
            }

            $metaData = TorrentData::fromTorrentUrl($title, $torrentUrl, $currentSeeds, Resolution::guessFromString($title));
            $results->add(new ProviderResult(
                ProviderType::provider($this->providerInformation->getName()),
                $metaData,
                $size
            ));
        }
        return $results->getResults();
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
