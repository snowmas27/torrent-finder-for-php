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

    // Index of the seeders <td> in result rows, when the listing table has a recognisable header
    private ?int $seedColumnIndex = null;

    // Matches human-readable file sizes: "2.87 GB", "695,45 MB", "1.4GiB".
    // The lookbehind (?<![\w.]) avoids capturing a trailing fragment when the number is
    // glued to preceding digits (e.g. "51714.46 GB" must yield 14.46, not 46), and the
    // lookahead (?![a-z]) lets the unit be followed by a digit (e.g. "14.46 GB0" from
    // concatenated table cells) while still rejecting words like "MBit" / "GBytes".
    private const SIZE_REGEX = '/(?<![\w.])(\d+(?:[.,]\d+)?)\s*(GiB|GIBYTE|GIB|GBS|GB|GO|MiB|MIBYTE|MIB|MBS|MB|MO|KiB|KIB|KB|KO)(?![a-z])/i';

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

    // Root element of an RSS or Atom document
    private const FEED_ROOT_REGEX = '/^\s*(<\?xml[^>]*>\s*)?(<!--.*?-->\s*)*<(rss|feed|rdf:RDF)[\s>]/is';

    // Table header text identifying the seeders column ("Seeds", "Seeders", "S", "SE", "↑")
    private const SEED_HEADER_REGEX = '/^(seed(s|ers?)?|se|s|↑|▲)$/iu';

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
        $baseUrl = $this->providerInformation->getSearchUrl()->getBaseUrl();
        $this->seedColumnIndex = null;

        try {
            $content = $this->fileGetContentsCurl($url);
        } catch (\Exception $e) {
            return [];
        }

        // RSS / Atom feeds must be parsed as XML: the HTML parser behind Crawler treats
        // <link> as a void element and drops namespaced tags such as <nyaa:infoHash>.
        if (preg_match(self::FEED_ROOT_REGEX, $content)) {
            return $this->parseFeed($content, $baseUrl);
        }

        $crawler = new Crawler($content);
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

    private function parseFeed(string $xml, string $baseUrl): array
    {
        $document = new \DOMDocument();
        $document->recover = true;
        $previousErrorMode = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml, LIBXML_NOCDATA | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorMode);

        if (!$loaded) {
            return [];
        }

        $results = new ProviderResults();

        foreach (['item', 'entry'] as $tagName) {
            foreach ($document->getElementsByTagNameNS('*', $tagName) as $itemNode) {
                try {
                    $result = $this->processFeedItem($itemNode, $baseUrl);
                    if ($result !== null) {
                        $results->add($result);
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return $results->getResults();
    }

    /**
     * Feeds put the download information in many different places depending on the site:
     *   - magnet in <link>, <enclosure url>, <magnetURI>, a torznab attr or the description HTML
     *   - bare info hash in <nyaa:infoHash>, <info_hash>, a torznab attr, the link or the description
     *   - .torrent URL in <enclosure url> or <link>
     * A magnet (given or rebuilt from the info hash) is preferred so results can be deduplicated.
     */
    private function processFeedItem(\DOMElement $itemNode, string $baseUrl): ?ProviderResult
    {
        $fields = [];
        $attributes = [];
        $links = [];

        foreach ($itemNode->childNodes as $child) {
            if (!$child instanceof \DOMElement) {
                continue;
            }
            $name = strtolower($child->localName);
            $value = trim($child->textContent);

            if ($name === 'attr' && $child->hasAttribute('name')) {
                // <torznab:attr name="seeders" value="12"/>
                $attributes[strtolower($child->getAttribute('name'))] = $child->getAttribute('value');
                continue;
            }
            if ($name === 'enclosure' || $name === 'link') {
                // Atom <link href="…"/> and RSS <enclosure url="…"/> carry the URL in an attribute
                $links[] = $child->getAttribute('url') ?: $child->getAttribute('href') ?: $value;
            }
            if (!isset($fields[$name])) {
                $fields[$name] = $value;
            }
        }

        $title = $fields['title'] ?? '';
        if ($title === '') {
            return null;
        }

        $description = html_entity_decode(
            $fields['description'] ?? $fields['summary'] ?? $fields['content'] ?? '',
            ENT_QUOTES | ENT_HTML5
        );
        $links = array_filter(array_merge(
            $links,
            [$fields['magneturi'] ?? '', $attributes['magneturl'] ?? '', $fields['guid'] ?? '']
        ));

        $download = $this->extractFeedDownload($title, $links, $fields, $attributes, $description, $baseUrl);
        if ($download === null) {
            return null;
        }

        $resolution = Resolution::guessFromString($title);
        $seeds = (int) ($fields['seeders'] ?? $fields['seeds'] ?? $attributes['seeders'] ?? 0);
        if ($seeds === 0 && preg_match('/Seed(?:s|ers)?\s*:?\s*(\d+)/i', $this->htmlToText($description), $m)) {
            $seeds = (int) $m[1];
        }

        $torrentData = $download['type'] === 'magnet'
            ? TorrentData::fromMagnetURI($title, $download['url'], $seeds, $resolution)
            : TorrentData::fromTorrentUrl($title, $download['url'], $seeds, $resolution);

        return new ProviderResult(
            ProviderType::provider($this->providerInformation->getName()),
            $torrentData,
            $this->extractFeedSize($itemNode, $fields, $attributes, $description) ?? new Size(0)
        );
    }

    /**
     * @return array{type: string, url: string}|null
     */
    private function extractFeedDownload(
        string $title,
        array $links,
        array $fields,
        array $attributes,
        string $description,
        string $baseUrl
    ): ?array {
        foreach ($links as $link) {
            if (str_starts_with($link, 'magnet:')) {
                return ['type' => 'magnet', 'url' => $link];
            }
        }

        if (preg_match('/magnet:\?[^"\'<>\s]+/i', $description, $m)) {
            return ['type' => 'magnet', 'url' => $m[0]];
        }

        $infoHash = $fields['infohash'] ?? $fields['info_hash'] ?? $fields['hash'] ?? $attributes['infohash'] ?? null;
        if ($infoHash === null) {
            foreach (array_merge($links, [$this->htmlToText($description)]) as $text) {
                if (preg_match('/(?<![0-9a-f])([0-9a-f]{40})(?![0-9a-f])/i', $text, $m)) {
                    $infoHash = $m[1];
                    break;
                }
            }
        }
        if ($infoHash !== null && preg_match('/^[0-9a-f]{40}$/i', $infoHash)) {
            return [
                'type' => 'magnet',
                'url' => sprintf('magnet:?xt=urn:btih:%s&dn=%s', strtoupper($infoHash), rawurlencode($title)),
            ];
        }

        foreach ($links as $link) {
            if (str_ends_with(strtolower(parse_url($link, PHP_URL_PATH) ?? ''), '.torrent')) {
                return ['type' => 'torrent', 'url' => $this->resolveUrl($link, $baseUrl)];
            }
        }

        return null;
    }

    private function extractFeedSize(\DOMElement $itemNode, array $fields, array $attributes, string $description): ?Size
    {
        $enclosureLength = '';
        foreach ($itemNode->getElementsByTagName('enclosure') as $enclosure) {
            $enclosureLength = $enclosure->getAttribute('length');
        }

        foreach ([$fields['size'] ?? '', $fields['contentlength'] ?? '', $attributes['size'] ?? '', $enclosureLength] as $value) {
            if ($value === '') {
                continue;
            }
            if (ctype_digit($value) && (int) $value > 0) {
                return new Size((float) $value);
            }
            $size = $this->parseSizeFromText($value);
            if ($size !== null) {
                return $size;
            }
        }

        return $this->parseSizeFromText($this->htmlToText($description));
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
            $this->seedColumnIndex = $this->detectSeedColumnIndex($bestTable);
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

    /**
     * Finds the seeders column from the table header (<th> cells, or <td> cells of a <thead>).
     */
    private function detectSeedColumnIndex(Crawler $table): ?int
    {
        $headerCells = $table->filter('thead th, thead td');
        if ($headerCells->count() === 0) {
            $headerCells = $table->filter('tr')->reduce(fn(Crawler $tr) => $tr->filter('th')->count() > 1)->first()->filter('th');
        }

        foreach ($headerCells as $index => $cell) {
            $text = trim(preg_replace('/\s+/u', ' ', $cell->textContent));
            if (preg_match(self::SEED_HEADER_REGEX, $text)) {
                return $index;
            }
        }

        return null;
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
        if ($this->seedColumnIndex !== null && $row->nodeName() === 'tr') {
            $cells = $row->filter('td');
            if ($cells->count() > $this->seedColumnIndex) {
                $text = str_replace([',', '.', ' '], '', trim($cells->eq($this->seedColumnIndex)->text()));
                if (preg_match('/^\d{1,7}$/', $text)) {
                    return (int) $text;
                }
            }
        }

        $candidates = [];
        $rowNode = $row->getNode(0);

        $row->filter('td, th, span, div')->each(function (Crawler $cell) use (&$candidates, $rowNode) {
            // Numbers inside links are part of the title (e.g. a year), not a seeder count
            for ($node = $cell->getNode(0)->parentNode; $node !== null && $node !== $rowNode; $node = $node->parentNode) {
                if ($node->nodeName === 'a') {
                    return;
                }
            }

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
     * Extracts the first recognisable file size from the row.
     *
     * Sizes are parsed per-cell rather than from the whole row's concatenated text:
     * DomCrawler::text() joins sibling elements without whitespace, so a size cell
     * ("<span>14.46 GB</span>") gets glued to its neighbours ("51714.46 GB0") and the
     * unit ends up wedged between digits, defeating the regex. Scanning leaf cells keeps
     * each size string clean.
     */
    private function extractSize(Crawler $row): ?Size
    {
        $found = null;

        $row->filter('td, span, div, p, a')->each(function (Crawler $cell) use (&$found) {
            if ($found !== null) {
                return;
            }
            $size = $this->parseSizeFromText(trim($cell->text()));
            if ($size !== null) {
                $found = $size;
            }
        });

        // Fallback to the whole-row text for layouts without discrete size cells.
        return $found ?? $this->parseSizeFromText($row->text());
    }

    private function parseSizeFromText(string $text): ?Size
    {
        // Collapse non-breaking spaces (NBSP, narrow NBSP, figure space) to a regular
        // space — torrent listings routinely glue the number to the unit with "14.46&nbsp;GB",
        // which the \s in SIZE_REGEX would not match in PCRE's default (non-Unicode) mode.
        $text = preg_replace('/[\x{00a0}\x{2007}\x{202f}]/u', ' ', $text) ?? $text;

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

    /**
     * Strips tags while keeping a space where they were, so "file.mkv<br>39.21GB" does not
     * become "file.mkv39.21GB" (which SIZE_REGEX rejects).
     */
    private function htmlToText(string $html): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags(preg_replace('/<[^>]*>/', ' $0 ', $html))));
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
