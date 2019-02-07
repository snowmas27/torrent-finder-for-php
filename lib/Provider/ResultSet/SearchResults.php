<?php

namespace TorrentFinder\Provider\ResultSet;

class SearchResults
{
    /** @var ProviderResult[] */
    private $results;
    private $summary;

    public function __construct(array $results)
    {
        $this->results = $results;
        $this->updateSummary();
    }

    /**
     * @return ProviderResult[]
     */
    public function getResults(): array
    {
        return $this->sortBySeeds($this->results);
    }

    /**
     * @return ProviderResult[]
     */
    public function getResultsWithSeedsGreaterThan(int $seeds): array
    {
        $results = [];
        foreach ($this->results as $result) {
            if ($result->getTorrentMetaData()->getSeeds() < $seeds) {
                continue;
            }
            $results[] = $result;
        }

        return $this->sortBySeeds($results);
    }

    public function getSummary(): SearchResultSummary
    {
        return $this->summary;
    }

    /**
     * @return ProviderResult[]
     */
    private function sortBySeeds(array $results): array
    {
        usort($results, function(ProviderResult $a, ProviderResult $b) {
            return $a->getTorrentMetaData()->getSeeds() < $b->getTorrentMetaData()->getSeeds();
        });

        return $results;
    }

    private function updateSummary()
    {
        $score = [];
        $bestResults = $this->getResults();
        foreach ($bestResults as $result) {
            $providerName = $result->getProvider();
            $score[$providerName] = !isset($score[$providerName]) ? 1 : $score[$providerName] + 1;
        }

        $resultsFoundSummaryByProvider = [];
        foreach ($score as $providerName => $nbrResults) {
            $resultsFoundSummaryByProvider[] = new ResultsFoundSummaryByProvider($providerName, $nbrResults);
        }
        $this->summary = new SearchResultSummary($resultsFoundSummaryByProvider);
    }
}