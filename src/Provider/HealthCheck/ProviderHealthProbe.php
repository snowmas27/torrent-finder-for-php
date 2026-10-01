<?php

namespace TorrentFinder\Provider\HealthCheck;

use TorrentFinder\Provider\Jackett\JackettUrlBuilder;
use TorrentFinder\Provider\ProviderConfiguration;
use TorrentFinder\Search\CrawlerInformationExtractor;
use TorrentFinder\Search\SearchQueryBuilder;

/**
 * Checks that a provider answers a search request.
 *
 * Unlike Provider::search(), failures are not swallowed: a probe returns silently when the
 * source answered (even with no result) and throws when it is down, too slow or blocked.
 */
class ProviderHealthProbe
{
    use CrawlerInformationExtractor;

    private JackettUrlBuilder $jackettUrlBuilder;

    public function __construct(JackettUrlBuilder $jackettUrlBuilder)
    {
        $this->jackettUrlBuilder = $jackettUrlBuilder;
    }

    /**
     * @throws \UnexpectedValueException when the provider does not answer correctly
     */
    public function probeProvider(ProviderConfiguration $configuration, SearchQueryBuilder $query): void
    {
        $url = sprintf(
            $configuration->getInformation()->getSearchUrl()->getUrl(),
            $query->rawUrlEncode()
        );

        $this->fileGetContentsCurl($url, true);
    }

    /**
     * @throws \UnexpectedValueException when the indexer does not answer correctly
     */
    public function probeJackett(string $indexer, SearchQueryBuilder $query): void
    {
        $url = $this->jackettUrlBuilder->buildSearchByIndexerUrl($indexer, $query->urlize());
        $content = $this->fileGetContentsCurl($url, true);

        if (1 === preg_match('/^\s*(<\?xml[^>]*>\s*)?<error\b/i', $content)) {
            throw new \UnexpectedValueException(sprintf('Jackett indexer "%s" answered an error', $indexer));
        }
    }
}
