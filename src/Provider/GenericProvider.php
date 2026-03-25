<?php

namespace TorrentFinder\Provider;

use Symfony\Component\DomCrawler\Crawler;
use TorrentFinder\Provider\ResultSet\ProviderResult;
use TorrentFinder\Provider\ResultSet\ProviderResults;
use TorrentFinder\Provider\ResultSet\TorrentData;
use TorrentFinder\Search\CrawlerInformationExtractor;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\VideoSettings\Resolution;
use TorrentFinder\VideoSettings\Size;
use TorrentFinder\VideoSettings\SizeFactory;

/**
 * Universal torrent site scraper.
 *
 * Works with any searchUrl without site-specific code. Supports:
 *   - RSS / Atom feeds
 *   - HTML table-based listings
 *   - HTML div/card-based listings
 *
 * For each result row it extracts:
 *   title, magnet URI or .torrent URL, seeds, size, resolution.
 *
 * Strategy priority:
 *   1. RSS/Atom detection (fastest; structured data)
 *   2. Magnet link found directly in the listing row (no extra request)
 *   3. .torrent link found directly in the listing row
 *   4. Follow the detail-page link and look for magnet/.torrent there
 */
class GenericProvider implements Provider
{
    use CrawlerInformationExtractor;

    private ProviderInformation $providerInformation;

    // Matches human-readable file sizes: "2.87 GB", "695,45 MB", "1.4GiB"
    private const SIZE_REGEX = '/\b(\d+(?:[.,]\d+)?)\s*(GiB|GIBYTE|GIB|GBS|GB|GO|MiB|MIBYTE|MIB|MBS|MB|MO|KiB|KIB|KB|KO)\b/i';

    // Link href patterns that indicate navigation / category links (excluded from title candidates)
    private const SKIP_HREF_PATTERNS = [
        '/category/',
        '/genre/',
        '/browse/',
        '/cat/',
        '/sort/',
        '/order/',
        'javascript:',
        '/page/',
        '/tag/',
        '/user/',
        '/profile/',
        '/filter',
        '/login',
        '/register',
        '/search',
    ];

    // Patterns strongly associated with torrent release names
    private const TORRENT_NAME_REGEX = '/\b(\d{4}|\d{3,4}p|mkv|avi|mp4|xvid|x26[45]|hevc|bluray|webrip|web|hdtv|dvdrip|bdrip|hdcam|french|multi|vostfr|english|dual|vff|truefrench|extreme|yify|rarbg)\b/i';

    // CSS class / attribute keywords associated with seeder columns
    private const SEED_KEYWORDS = ['seed', 'seeder', 'se', 'up'];

    public function __construct(ProviderInformation $providerInformation)
    {
        $this->providerInformation = $providerInformation;
    }

    // -------------------------------------------------------------------------
    // Entry point
    // -------------------------------------------------------------------------

    public function search(SearchQueryBuilder $keywords): array
    {
        $results = new ProviderResults();
        $url = sprintf(
            $this->providerInformation->getSearchUrl()->getUrl(),
            $keywords->rawUrlEncode()
        );
        dump("Searching {$this->getName()} with URL: $url");
        $baseUrl = $this->providerInformation->getSearchUrl()->getBaseUrl();

        try {
            $crawler = $this->initDomCrawler($url);
        } catch (\Exception $e) {
            return [];
        }

        // RSS / Atom feeds: <item> or <entry> with <title> child
        if ($crawler->filter('item > title, entry > title')->count() > 0) {
            return $this->parseRss($crawler);
        }

        $rows = $this->detectResultRows($crawler);

        foreach ($rows as $rowNode) {
            try {
                $row = new Crawler($rowNode);
                $result = $this->processRow($row, $baseUrl);
                if ($result !== null) {
                    $results->add($result);
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $results->getResults();
    }

    // =========================================================================
    // RSS / Atom parsing
    // =========================================================================

    private function parseRss(Crawler $crawler): array
    {
        $results = new ProviderResults();

        foreach ($crawler->filter('item, entry') as $itemNode) {
            try {
                $item = new Crawler($itemNode);

                $title = trim($item->filter('title')->text());
                if ($title === '') {
                    continue;
                }

                $magnet = null;
                $torrentUrl = null;

                // <enclosure> tag (used by most RSS torrent feeds)
                if ($item->filter('enclosure')->count() > 0) {
                    $encUrl = $item->filter('enclosure')->attr('url') ?? '';
                    if (str_starts_with($encUrl, 'magnet:')) {
                        $magnet = $encUrl;
                    } elseif ($encUrl !== '') {
                        $torrentUrl = $encUrl;
                    }
                }

                // <link> element (used by some feeds for direct magnet URIs)
                if ($magnet === null && $torrentUrl === null && $item->filter('link')->count() > 0) {
                    $link = trim($item->filter('link')->text());
                    if (str_starts_with($link, 'magnet:')) {
                        $magnet = $link;
                    } elseif ($link !== '') {
                        $torrentUrl = $link;
                    }
                }

                // Explicit <magnetURI> or <magneturi> tag (some custom feeds)
                if ($magnet === null) {
                    $magnetTag = $item->filter('magnetURI, magnetUri, magneturi');
                    if ($magnetTag->count() > 0) {
                        $magnet = trim($magnetTag->text());
                    }
                }

                if ($magnet === null && $torrentUrl === null) {
                    continue;
                }

                // Size from <description> CDATA
                $size = new Size(0);
                if ($item->filter('description')->count() > 0) {
                    try {
                        $desc = $item->filter('description')->html();
                        $size = $this->parseSizeFromText(strip_tags($desc)) ?? new Size(0);
                    } catch (\Exception $e) {
                    }
                }

                $seeds = 0;
                if ($item->filter('seeders, seeds')->count() > 0) {
                    $seeds = (int) $item->filter('seeders, seeds')->text();
                }

                $resolution = Resolution::guessFromString($title);
                $torrentData = $magnet !== null
                    ? TorrentData::fromMagnetURI($title, $magnet, $seeds, $resolution)
                    : TorrentData::fromTorrentUrl($title, $torrentUrl, $seeds, $resolution);

                $results->add(new ProviderResult(
                    ProviderType::provider($this->providerInformation->getName()),
                    $torrentData,
                    $size
                ));
            } catch (\Exception $e) {
                continue;
            }
        }

        return $results->getResults();
    }

    // =========================================================================
    // Row / card detection
    // =========================================================================

    /**
     * Returns DOMNode[] representing individual torrent result rows.
     *
     * Strategy 1 – Table: pick the table with the highest (rows × cols) score.
     * Strategy 2 – Divs: pick the most-repeated div class (≥3 occurrences).
     */
    private function detectResultRows(Crawler $crawler): array
    {
        // --- Strategy 1: dominant table ---
        $bestTable = null;
        $bestScore = 0;

        $crawler->filter('table')->each(function (Crawler $table) use (&$bestTable, &$bestScore) {
            $dataRows = 0;
            $maxCols = 0;

            $table->filter('tr')->each(function (Crawler $tr) use (&$dataRows, &$maxCols) {
                $tds = $tr->filter('td')->count();
                if ($tds < 2) {
                    return;
                }
                $dataRows++;
                if ($tds > $maxCols) {
                    $maxCols = $tds;
                }
            });

            if ($dataRows < 2) {
                return;
            }

            $score = $dataRows * $maxCols;
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestTable = $table;
            }
        });

        if ($bestTable !== null && $bestScore >= 6) {
            $rows = [];
            $bestTable->filter('tr')->each(function (Crawler $tr) use (&$rows) {
                // Skip header rows (no <td> children)
                if ($tr->filter('td')->count() < 2) {
                    return;
                }
                $rows[] = $tr->getNode(0);
            });
            if (count($rows) >= 2) {
                return $rows;
            }
        }

        // --- Strategy 2: repeated div class ---
        $classCounts = [];
        $crawler->filter('div[class]')->each(function (Crawler $div) use (&$classCounts) {
            $raw = trim($div->attr('class') ?? '');
            // Only use first class token to avoid noise from utility classes
            $parts = preg_split('/\s+/', $raw);
            $firstClass = $parts[0] ?? '';
            if (strlen($firstClass) < 3) {
                return;
            }
            $classCounts[$firstClass] = ($classCounts[$firstClass] ?? 0) + 1;
        });

        arsort($classCounts);

        foreach ($classCounts as $class => $count) {
            if ($count < 3) {
                break;
            }

            // Escape special CSS selector characters
            $escapedClass = preg_replace('/([.#\[\]:()!])/', '\\\\$1', $class);

            try {
                $rows = [];
                $crawler->filter("div.$escapedClass")->each(function (Crawler $div) use (&$rows) {
                    // Must have at least one link and some text content
                    if ($div->filter('a')->count() < 1) {
                        return;
                    }
                    if (strlen(trim($div->text())) < 20) {
                        return;
                    }
                    $rows[] = $div->getNode(0);
                });

                if (count($rows) >= 3) {
                    return $rows;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return [];
    }

    // =========================================================================
    // Process a single result row / card
    // =========================================================================

    private function processRow(Crawler $row, string $baseUrl): ?ProviderResult
    {
        $titleInfo = $this->extractTitleInfo($row, $baseUrl);
        if ($titleInfo === null) {
            return null;
        }

        ['text' => $title, 'href' => $titleHref] = $titleInfo;

        $downloadInfo = $this->extractDownloadInfo($row, $titleHref, $baseUrl);
        if ($downloadInfo === null) {
            return null;
        }

        $size = $this->extractSize($row) ?? new Size(0);
        $seeds = $this->extractSeeds($row);

        $resolution = Resolution::guessFromString($title);
        $torrentData = $downloadInfo['type'] === 'magnet'
            ? TorrentData::fromMagnetURI($title, $downloadInfo['url'], $seeds, $resolution)
            : TorrentData::fromTorrentUrl($title, $downloadInfo['url'], $seeds, $resolution);

        return new ProviderResult(
            ProviderType::provider($this->providerInformation->getName()),
            $torrentData,
            $size
        );
    }

    // =========================================================================
    // Field-extraction helpers
    // =========================================================================

    /**
     * Picks the best candidate for the torrent title from all <a> links in a row.
     *
     * Scoring:
     *   + length of the link text (longer ≈ more likely a full release name)
     *   + 100 pts if text matches TORRENT_NAME_REGEX patterns (year, codec, lang…)
     * Links are excluded if their href matches navigational patterns.
     *
     * @return array{text: string, href: string}|null
     */
    private function extractTitleInfo(Crawler $row, string $baseUrl): ?array
    {
        $bestText = null;
        $bestHref = null;
        $bestScore = -1;

        $row->filter('a')->each(function (Crawler $a) use (&$bestText, &$bestHref, &$bestScore, $baseUrl) {
            $href = $a->attr('href') ?? '';
            $text = trim($a->text());

            // Must have readable text
            if (strlen($text) < 5) {
                return;
            }

            // Skip images-only links
            if ($a->filter('img')->count() > 0 && $text === '') {
                return;
            }

            // Skip navigation / category / search links
            $lowerHref = strtolower($href);
            foreach (self::SKIP_HREF_PATTERNS as $pattern) {
                if (str_contains($lowerHref, $pattern)) {
                    return;
                }
            }

            // Skip if the href itself is the download (magnet or .torrent) –
            // those are handled separately
            if (str_starts_with($lowerHref, 'magnet:')) {
                return;
            }
            if (str_ends_with(strtolower(parse_url($href, PHP_URL_PATH) ?? ''), '.torrent')) {
                return;
            }

            $score = strlen($text);
            if (preg_match(self::TORRENT_NAME_REGEX, $text)) {
                $score += 100;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestText = $text;
                $bestHref = $this->resolveUrl($href, $baseUrl);
            }
        });

        if ($bestText === null) {
            return null;
        }

        return ['text' => $bestText, 'href' => $bestHref];
    }

    /**
     * Looks for a download link in this order:
     *   1. magnet: link directly in the row → no extra HTTP request
     *   2. .torrent link directly in the row → no extra HTTP request
     *   3. Detail page linked from the row → one extra HTTP request
     *      (fetches once and checks for both magnet then .torrent)
     *
     * @return array{type: string, url: string}|null
     */
    private function extractDownloadInfo(Crawler $row, ?string $detailHref, string $baseUrl): ?array
    {
        $magnet = null;
        $torrentUrl = null;

        $row->filter('a')->each(function (Crawler $a) use (&$magnet, &$torrentUrl, $baseUrl) {
            if ($magnet !== null) {
                return; // already found
            }
            $href = $a->attr('href') ?? '';

            if (str_starts_with($href, 'magnet:')) {
                $magnet = $href;
            } elseif ($torrentUrl === null) {
                $path = strtolower(parse_url($href, PHP_URL_PATH) ?? '');
                if (str_ends_with($path, '.torrent')) {
                    $torrentUrl = $this->resolveUrl($href, $baseUrl);
                }
            }
        });

        if ($magnet !== null) {
            return ['type' => 'magnet', 'url' => $magnet];
        }
        if ($torrentUrl !== null) {
            return ['type' => 'torrent', 'url' => $torrentUrl];
        }

        // Detail-page fallback — single fetch, check magnet then .torrent
        if ($detailHref === null || !str_starts_with($detailHref, 'http')) {
            return null;
        }

        try {
            $detailCrawler = $this->initDomCrawler($detailHref);

            // Magnet link
            $magnets = $detailCrawler->filter('a[href*="magnet:"]');
            if ($magnets->count() > 0) {
                return ['type' => 'magnet', 'url' => $magnets->first()->attr('href')];
            }

            // .torrent link
            $torrentOnDetail = null;
            $detailCrawler->filter('a')->each(function (Crawler $a) use (&$torrentOnDetail, $baseUrl) {
                if ($torrentOnDetail !== null) {
                    return;
                }
                $href = $a->attr('href') ?? '';
                $path = strtolower(parse_url($href, PHP_URL_PATH) ?? '');
                if (str_ends_with($path, '.torrent')) {
                    $torrentOnDetail = $this->resolveUrl($href, $baseUrl);
                }
            });

            if ($torrentOnDetail !== null) {
                return ['type' => 'torrent', 'url' => $torrentOnDetail];
            }
        } catch (\Exception $e) {
            // Detail page unreachable; give up on this row
        }

        return null;
    }

    /**
     * Extracts seeds from the row.
     *
     * Scans every cell/span/div for a pure-integer text value, scoring each:
     *   +100 pts if the element's class contains a "seed" keyword
     *   + 50 pts if the element has a green-ish inline color style
     * Returns the highest-scoring candidate (default 0).
     */
    private function extractSeeds(Crawler $row): int
    {
        $candidates = [];

        $row->filter('td, th, span, div')->each(function (Crawler $cell) use (&$candidates) {
            $text = trim($cell->text());

            // Must be a clean integer (1–6 digits)
            if (!preg_match('/^\d{1,6}$/', $text)) {
                return;
            }

            $value = (int) $text;
            $class = strtolower($cell->attr('class') ?? '');
            $style = strtolower($cell->attr('style') ?? '');

            $score = 0;
            foreach (self::SEED_KEYWORDS as $kw) {
                if (str_contains($class, $kw)) {
                    $score += 100;
                    break;
                }
            }

            // Green-ish inline color → typically the seeder count
            if (preg_match('/color\s*:\s*(green|lime|#0[0-9a-f]{5}|#[0-9a-f]{3})/i', $style)) {
                $score += 50;
            }

            $candidates[] = ['value' => $value, 'score' => $score];
        });

        if (empty($candidates)) {
            return 0;
        }

        // Highest score wins; on tie prefer the larger integer (more seeds = better signal)
        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score'] ?: $b['value'] <=> $a['value']);

        return $candidates[0]['value'];
    }

    /**
     * Extracts the first recognisable file size from the row's combined text.
     */
    private function extractSize(Crawler $row): ?Size
    {
        return $this->parseSizeFromText($row->text());
    }

    private function parseSizeFromText(string $text): ?Size
    {
        if (!preg_match(self::SIZE_REGEX, $text, $m)) {
            return null;
        }

        try {
            // Normalise comma decimal separator and pass to SizeFactory
            $sizeString = str_replace(',', '.', $m[1]) . ' ' . strtoupper($m[2]);
            return SizeFactory::fromHumanSize($sizeString);
        } catch (\Exception $e) {
            return null;
        }
    }

    // =========================================================================
    // URL utilities
    // =========================================================================

    private function resolveUrl(string $href, string $baseUrl): string
    {
        if ($href === '' || str_starts_with($href, 'magnet:') || str_starts_with($href, 'http')) {
            return $href;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($href, '/');
    }

    // =========================================================================
    // Provider interface
    // =========================================================================

    public function getName(): string
    {
        return $this->providerInformation->getName();
    }
}
