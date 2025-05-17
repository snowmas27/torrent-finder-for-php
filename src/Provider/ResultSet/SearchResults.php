<?php

namespace TorrentFinder\Provider\ResultSet;

use Assert\Assertion;

class SearchResults
{
    /** @var ProviderResult[] */
    private $results;
    private $summary;

    /**
     * @param ProviderResult[] $results
     */
    public static function fromArray(array $data): self
    {
        $results = [];
        foreach ($data as $result) {
            $results[] = ProviderResult::fromArray($result);
        }

        return new self($results);
    }

    public function __construct(array $results)
    {
        Assertion::allIsInstanceOf($results, ProviderResult::class);
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
    public function getResultsWithSeedsGreaterThan(int $seeds, array $resolutions = null): array
    {
        $results = [];
        foreach ($this->results as $result) {
            if (null !== $resolutions && !in_array($result->getTorrentMetaData()->getFormat()->getValue(), $resolutions)) {
                continue;
            }

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
        usort($results, function (ProviderResult $a, ProviderResult $b) {
            return $a->getTorrentMetaData()->getSeeds() < $b->getTorrentMetaData()->getSeeds() ? 1 : -1;
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
