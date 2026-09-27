<?php

namespace TorrentFinder\Provider;

use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\ProviderResults;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\CrawlerInformationExtractor;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Resolution;
use TorrentFinder\VideoSettings\Size;

/**
 * The Pirate Bay through its JSON API (apibay.org).
 *
 * thepiratebay.org only serves a JavaScript shell; the actual data comes from
 * apibay.org/q.php, which returns a JSON array of torrents (info_hash, seeders, size in bytes).
 */
class ThePirateBay implements Provider
{
    use CrawlerInformationExtractor;

    // Returned by apibay as the single entry when nothing matches.
    private const EMPTY_INFO_HASH = '0000000000000000000000000000000000000000';

    private const TRACKERS = [
        'udp://tracker.opentrackr.org:1337/announce',
        'udp://open.stealth.si:80/announce',
        'udp://tracker.torrent.eu.org:451/announce',
        'udp://exodus.desync.com:6969/announce',
        'udp://tracker.openbittorrent.com:6969/announce',
    ];

    private $providerInformation;

    public function __construct(ProviderInformation $providerInformation)
    {
        $this->providerInformation = $providerInformation;
    }

    public function search(SearchQueryBuilder $keywords): array
    {
        $results = new ProviderResults();
        $url = sprintf($this->providerInformation->getSearchUrl()->getUrl(), $keywords->urlize());
        $items = json_decode($this->fileGetContentsCurl($url), true);

        if (!is_array($items)) {
            return [];
        }

        foreach ($items as $item) {
            $infoHash = $item['info_hash'] ?? self::EMPTY_INFO_HASH;
            if (self::EMPTY_INFO_HASH === $infoHash || empty($item['name'])) {
                continue;
            }

            $title = trim($item['name']);
            $results->add(new ProviderResult(
                ProviderType::provider($this->providerInformation->getName()),
                TorrentData::fromMagnetURI(
                    $title,
                    $this->buildMagnet($infoHash, $title),
                    (int) ($item['seeders'] ?? 0),
                    Resolution::guessFromString($title)
                ),
                new Size((float) ($item['size'] ?? 0))
            ));
        }

        return $results->getResults();
    }

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }

    private function buildMagnet(string $infoHash, string $title): string
    {
        $magnet = sprintf('magnet:?xt=urn:btih:%s&dn=%s', $infoHash, rawurlencode($title));
        foreach (self::TRACKERS as $tracker) {
            $magnet .= '&tr=' . rawurlencode($tracker);
        }

        return $magnet;
    }
}
